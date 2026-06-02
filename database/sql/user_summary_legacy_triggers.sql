-- Snapshot of legacy user_summary PL/pgSQL functions + triggers.
-- Captured 2026-06-02 13:23:45 for migration rollback (down()).
-- These are the ORIGINAL (pre-refactor) definitions; the projection is
-- now maintained by App\Services\User\UserSummaryService via observers.

CREATE OR REPLACE FUNCTION public.refresh_user_summary()
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
  debit_types  TEXT[] := ARRAY[
    'deposit',
    'withdraw_package_profit',
	'withdraw_package_reinvest_profit',
    'withdraw_package',
    'hidden_deposit'
  ];
  credit_types TEXT[] := ARRAY[
    'buy_package',
    'withdraw'
  ];
BEGIN
  -- 1) Очищаем предыдущие данные
  TRUNCATE user_summary;

  -- 2) Пересчитываем и вставляем агрегаты
  INSERT INTO user_summary (
    user_id,
    investments_sum,
	buy_packages_sum,
    reinvests_sum,
    partner_balance,
    partners_count,
    first_package_at
  )
  SELECT
    u.id AS user_id,

    -- 1) основной баланс: дебет +, кредит –
    COALESCE(tx_dep_buy.sum_amount, 0) AS investments_sum,

	COALESCE(tx_buy_packages.sum_amount, 0) AS buy_packages_sum,

    -- 2) реинвесты
    COALESCE(tx_reinvest.sum_amount, 0) AS reinvests_sum,

    -- 3) партнёрский баланс
    COALESCE(tx_partner.sum_amount, 0) AS partner_balance,

    -- 4) число партнёров
    COALESCE(p.count_partners, 0) AS partners_count,

    -- 5) дата первого пакета
    tx_first.first_at

  FROM users u

  -- основной баланс
  LEFT JOIN (
    SELECT
      user_id,
      SUM(
        CASE
          WHEN trx_type = ANY(debit_types)  THEN  amount
          WHEN trx_type = ANY(credit_types) THEN -amount
          ELSE 0
        END
      ) AS sum_amount
    FROM transactions
	WHERE accepted_at IS NOT NULL
    GROUP BY user_id
  ) tx_dep_buy
    ON tx_dep_buy.user_id = u.id

  LEFT JOIN (
	  SELECT
	    user_id,
	    SUM(amount) AS sum_amount
	  FROM transactions
	  WHERE trx_type = 'buy_package'
	    AND accepted_at IS NOT NULL
	  GROUP BY user_id
	) tx_buy_packages
	  ON tx_buy_packages.user_id = u.id

  -- реинвесты через связь с package_profit_reinvests
  LEFT JOIN (
    SELECT
      t.user_id,
      SUM(r.amount) AS sum_amount
    FROM package_profit_reinvests r
    JOIN itc_packages p ON p.uuid = r.package_uuid
    JOIN transactions  t ON t.uuid  = p.uuid
    GROUP BY t.user_id
  ) tx_reinvest
    ON tx_reinvest.user_id = u.id

  -- партнёрский баланс
  LEFT JOIN (
    SELECT
      user_id,
      SUM(amount) AS sum_amount
    FROM transactions
    WHERE balance_type = 'partner'
    GROUP BY user_id
  ) tx_partner
    ON tx_partner.user_id = u.id

  -- число партнёров
  LEFT JOIN (
    SELECT
      partner_id   AS user_id,
      COUNT(*)     AS count_partners
    FROM partners
    GROUP BY partner_id
  ) p
    ON p.user_id = u.id

  -- дата первого пакета
  LEFT JOIN (
    SELECT
      user_id,
      MIN(accepted_at) AS first_at
    FROM transactions
    WHERE trx_type = 'buy_package'
    GROUP BY user_id
  ) tx_first
    ON tx_first.user_id = u.id
  ;
END;
$function$;

CREATE OR REPLACE FUNCTION public.trg_user_summary_on_user_insert()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN
  INSERT INTO user_summary(user_id)
  VALUES (NEW.id);
  RETURN NEW;
END;
$function$;

CREATE OR REPLACE FUNCTION public.trg_user_summary_on_partner()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
BEGIN
  IF TG_OP = 'INSERT' THEN
    UPDATE user_summary
       SET partners_count = partners_count + 1
     WHERE user_id = NEW.partner_id;
  ELSIF TG_OP = 'DELETE' THEN
    UPDATE user_summary
       SET partners_count = partners_count - 1
     WHERE user_id = OLD.partner_id;
  END IF;

  RETURN NULL;
END;
$function$;

CREATE OR REPLACE FUNCTION public.trg_user_summary_on_reinvest()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
  uid INTEGER;
BEGIN
  -- найдём user_id через таблицу пакетов → транзакции
  SELECT t.user_id INTO uid
    FROM itc_packages p
    JOIN transactions t ON t.uuid = p.uuid
   WHERE p.uuid = COALESCE(NEW.package_uuid, OLD.package_uuid);

  IF TG_OP = 'INSERT' THEN
    UPDATE user_summary
       SET reinvests_sum = reinvests_sum + NEW.amount
     WHERE user_id = uid;
  ELSIF TG_OP = 'DELETE' THEN
    UPDATE user_summary
       SET reinvests_sum = reinvests_sum - OLD.amount
     WHERE user_id = uid;
  END IF;

  RETURN NULL;
END;
$function$;

CREATE OR REPLACE FUNCTION public.trg_user_summary_on_reinvest_withdraw()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
  uid  INTEGER;
  amt  NUMERIC;
BEGIN
  SELECT
     t.user_id,
     rpr.amount
    INTO uid, amt
    FROM itc_packages p
    JOIN transactions t
      ON t.uuid = p.uuid
    JOIN package_profit_reinvests rpr
      ON rpr.uuid = COALESCE(NEW.reinvest_uuid, OLD.reinvest_uuid)
   WHERE p.uuid = rpr.package_uuid;

  IF TG_OP = 'INSERT' THEN
    UPDATE user_summary
       SET reinvests_sum = reinvests_sum - amt
     WHERE user_id = uid;
  ELSIF TG_OP = 'DELETE' THEN
    UPDATE user_summary
       SET reinvests_sum = reinvests_sum + amt
     WHERE user_id = uid;
  END IF;

  RETURN NULL;
END;
$function$;

CREATE OR REPLACE FUNCTION public.trg_user_summary_on_transaction()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
            DECLARE
              debit_types  TEXT[] := ARRAY[
                'deposit',
                'withdraw_package_profit',
                'withdraw_package_reinvest_profit',
                'withdraw_package',
                'hidden_deposit',
                'partner_to_main_self',
                'partner_transfer_in',
                'rank_bonus_accrual',
                'start_bonus_accrual',
                'regular_premium_accrual',
                'regular_premium_to_partner',
                'withdraw_package_to_balance'
              ];
              credit_types TEXT[] := ARRAY[
                'buy_package',
                'withdraw',
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'regular_premium_to_partner_mirror',
                'partner_bonus_rollback',
                'partner_to_package'
              ];

              partner_credit_types TEXT[] := ARRAY[
                'partner_to_main_self_mirror',
                'partner_transfer_out',
                'partner_to_package'
              ];

              commission NUMERIC;

            BEGIN
              IF TG_OP = 'INSERT' THEN
                -- при дебете (+investments_sum)
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                -- при кредите (-investments_sum)
                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                -- first_package_at
                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'DELETE' THEN
                -- откат по OLD: дебет
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                -- откат по OLD: кредит
                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                -- откат first_package_at
                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

              ELSIF TG_OP = 'UPDATE' THEN
                -- откат OLD
                IF OLD.balance_type <> 'partner' THEN
                  IF OLD.trx_type = 'deposit' AND OLD.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  ELSIF OLD.trx_type = ANY(debit_types) AND OLD.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum - OLD.amount
                     WHERE user_id = OLD.user_id;
                  END IF;
                END IF;

                IF OLD.trx_type = 'withdraw' AND OLD.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                     WHERE user_id = OLD.user_id;
                ELSIF OLD.trx_type = ANY(credit_types) AND OLD.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum + OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = (
                       SELECT MIN(t.accepted_at)
                         FROM transactions t
                        WHERE t.user_id  = OLD.user_id
                          AND t.trx_type = 'buy_package'
                          AND t.accepted_at IS NOT NULL
                     )
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum - OLD.amount
                   WHERE user_id = OLD.user_id;
                END IF;

                IF OLD.balance_type = 'partner' THEN
                    IF OLD.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance + OLD.amount
                         WHERE user_id = OLD.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance - OLD.amount
                         WHERE user_id = OLD.user_id;
                    END IF;
                END IF;

                -- применение NEW
                IF NEW.balance_type <> 'partner' THEN
                  IF NEW.trx_type = 'deposit' AND NEW.accepted_at IS NOT NULL THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  ELSIF NEW.trx_type = ANY(debit_types) AND NEW.trx_type <> 'deposit' THEN
                    UPDATE user_summary
                       SET investments_sum = investments_sum + NEW.amount
                     WHERE user_id = NEW.user_id;
                  END IF;
                END IF;

                IF NEW.trx_type = 'withdraw' AND NEW.accepted_at IS NOT NULL THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                     WHERE user_id = NEW.user_id;
                ELSIF NEW.trx_type = ANY(credit_types) AND NEW.trx_type <> 'withdraw' THEN
                  UPDATE user_summary
                     SET investments_sum = investments_sum - NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET first_package_at = LEAST(
                       COALESCE(first_package_at, NEW.accepted_at),
                       NEW.accepted_at
                     )
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.trx_type = 'buy_package' THEN
                  UPDATE user_summary
                     SET buy_packages_sum = buy_packages_sum + NEW.amount
                   WHERE user_id = NEW.user_id;
                END IF;

                IF NEW.balance_type = 'partner' THEN
                    IF NEW.trx_type = ANY(partner_credit_types) THEN
                        UPDATE user_summary
                           SET partner_balance = partner_balance - NEW.amount
                         WHERE user_id = NEW.user_id;
                    ELSE
                        UPDATE user_summary
                           SET partner_balance = partner_balance + NEW.amount
                         WHERE user_id = NEW.user_id;
                    END IF;
                END IF;

              END IF;

              RETURN NULL;
            END;
            $function$;

CREATE TRIGGER trg_reinvest_withdraw AFTER INSERT OR DELETE ON public.package_profit_reinvest_withdraws FOR EACH ROW EXECUTE FUNCTION trg_user_summary_on_reinvest_withdraw();
CREATE TRIGGER trg_user_summary_on_partner AFTER INSERT OR DELETE ON public.partners FOR EACH ROW EXECUTE FUNCTION trg_user_summary_on_partner();
CREATE TRIGGER trg_user_summary_on_reinvest AFTER INSERT OR DELETE ON public.package_profit_reinvests FOR EACH ROW EXECUTE FUNCTION trg_user_summary_on_reinvest();
CREATE TRIGGER trg_user_summary_on_transaction AFTER INSERT OR DELETE OR UPDATE ON public.transactions FOR EACH ROW EXECUTE FUNCTION trg_user_summary_on_transaction();
CREATE TRIGGER trg_user_summary_user_insert AFTER INSERT ON public.users FOR EACH ROW EXECUTE FUNCTION trg_user_summary_on_user_insert();
