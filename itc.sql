--
-- PostgreSQL database dump
--

-- Dumped from database version 16.2 (Debian 16.2-1.pgdg120+2)
-- Dumped by pg_dump version 16.2 (Debian 16.2-1.pgdg120+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: deposit_gifts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deposit_gifts (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    comment text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.deposit_gifts OWNER TO postgres;

--
-- Name: deposit_gifts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.deposit_gifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.deposit_gifts_id_seq OWNER TO postgres;

--
-- Name: deposit_gifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.deposit_gifts_id_seq OWNED BY public.deposit_gifts.id;


--
-- Name: deposits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deposits (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    commission numeric(16,8) NOT NULL,
    currency character varying(255) NOT NULL,
    transaction_hash character varying(255) NOT NULL,
    wallet_address character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.deposits OWNER TO postgres;

--
-- Name: deposits_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.deposits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.deposits_id_seq OWNER TO postgres;

--
-- Name: deposits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.deposits_id_seq OWNED BY public.deposits.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: itc_packages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.itc_packages (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    month_profit_percent numeric(8,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(255) NOT NULL,
    work_to timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.itc_packages OWNER TO postgres;

--
-- Name: itc_packages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.itc_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.itc_packages_id_seq OWNER TO postgres;

--
-- Name: itc_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.itc_packages_id_seq OWNED BY public.itc_packages.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: moonshine_socialites; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.moonshine_socialites (
    id bigint NOT NULL,
    moonshine_user_id bigint NOT NULL,
    driver character varying(255) NOT NULL,
    identity character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.moonshine_socialites OWNER TO postgres;

--
-- Name: moonshine_socialites_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.moonshine_socialites_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.moonshine_socialites_id_seq OWNER TO postgres;

--
-- Name: moonshine_socialites_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.moonshine_socialites_id_seq OWNED BY public.moonshine_socialites.id;


--
-- Name: moonshine_user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.moonshine_user_roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.moonshine_user_roles OWNER TO postgres;

--
-- Name: moonshine_user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.moonshine_user_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.moonshine_user_roles_id_seq OWNER TO postgres;

--
-- Name: moonshine_user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.moonshine_user_roles_id_seq OWNED BY public.moonshine_user_roles.id;


--
-- Name: moonshine_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.moonshine_users (
    id bigint NOT NULL,
    moonshine_user_role_id bigint DEFAULT '1'::bigint NOT NULL,
    email character varying(190) NOT NULL,
    password character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    avatar character varying(255),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.moonshine_users OWNER TO postgres;

--
-- Name: moonshine_users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.moonshine_users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.moonshine_users_id_seq OWNER TO postgres;

--
-- Name: moonshine_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.moonshine_users_id_seq OWNED BY public.moonshine_users.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- Name: package_profit_reinvests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_profit_reinvests (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    package_uuid character varying(255) NOT NULL,
    amount numeric(16,8) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.package_profit_reinvests OWNER TO postgres;

--
-- Name: package_profit_reinvests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_profit_reinvests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_profit_reinvests_id_seq OWNER TO postgres;

--
-- Name: package_profit_reinvests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_profit_reinvests_id_seq OWNED BY public.package_profit_reinvests.id;


--
-- Name: package_profit_withdraws; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_profit_withdraws (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    package_uuid character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.package_profit_withdraws OWNER TO postgres;

--
-- Name: package_profit_withdraws_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_profit_withdraws_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_profit_withdraws_id_seq OWNER TO postgres;

--
-- Name: package_profit_withdraws_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_profit_withdraws_id_seq OWNED BY public.package_profit_withdraws.id;


--
-- Name: package_profits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_profits (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    amount numeric(16,8) NOT NULL,
    package_uuid character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.package_profits OWNER TO postgres;

--
-- Name: package_profits_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_profits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_profits_id_seq OWNER TO postgres;

--
-- Name: package_profits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_profits_id_seq OWNED BY public.package_profits.id;


--
-- Name: package_reinvests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_reinvests (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    package_uuid character varying(255) NOT NULL,
    expire timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.package_reinvests OWNER TO postgres;

--
-- Name: package_reinvests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_reinvests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_reinvests_id_seq OWNER TO postgres;

--
-- Name: package_reinvests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_reinvests_id_seq OWNED BY public.package_reinvests.id;


--
-- Name: package_withdraws; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_withdraws (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    package_uuid character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.package_withdraws OWNER TO postgres;

--
-- Name: package_withdraws_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_withdraws_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_withdraws_id_seq OWNER TO postgres;

--
-- Name: package_withdraws_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_withdraws_id_seq OWNED BY public.package_withdraws.id;


--
-- Name: partners; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.partners (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    partner_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.partners OWNER TO postgres;

--
-- Name: partners_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.partners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.partners_id_seq OWNER TO postgres;

--
-- Name: partners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.partners_id_seq OWNED BY public.partners.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    amount numeric(16,8) NOT NULL,
    user_id bigint NOT NULL,
    balance_type character varying(255) NOT NULL,
    trx_type character varying(255) NOT NULL,
    accepted_at timestamp(0) without time zone,
    rejected_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    username character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    banned_at timestamp(0) without time zone,
    rank integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: withdraws; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.withdraws (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    commission numeric(16,8) NOT NULL,
    wallet_address character varying(255) NOT NULL,
    currency character varying(255) NOT NULL,
    trx_hash character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.withdraws OWNER TO postgres;

--
-- Name: withdraws_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.withdraws_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.withdraws_id_seq OWNER TO postgres;

--
-- Name: withdraws_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.withdraws_id_seq OWNED BY public.withdraws.id;


--
-- Name: deposit_gifts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposit_gifts ALTER COLUMN id SET DEFAULT nextval('public.deposit_gifts_id_seq'::regclass);


--
-- Name: deposits id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposits ALTER COLUMN id SET DEFAULT nextval('public.deposits_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: itc_packages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.itc_packages ALTER COLUMN id SET DEFAULT nextval('public.itc_packages_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: moonshine_socialites id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_socialites ALTER COLUMN id SET DEFAULT nextval('public.moonshine_socialites_id_seq'::regclass);


--
-- Name: moonshine_user_roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_user_roles ALTER COLUMN id SET DEFAULT nextval('public.moonshine_user_roles_id_seq'::regclass);


--
-- Name: moonshine_users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_users ALTER COLUMN id SET DEFAULT nextval('public.moonshine_users_id_seq'::regclass);


--
-- Name: package_profit_reinvests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_reinvests ALTER COLUMN id SET DEFAULT nextval('public.package_profit_reinvests_id_seq'::regclass);


--
-- Name: package_profit_withdraws id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_withdraws ALTER COLUMN id SET DEFAULT nextval('public.package_profit_withdraws_id_seq'::regclass);


--
-- Name: package_profits id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profits ALTER COLUMN id SET DEFAULT nextval('public.package_profits_id_seq'::regclass);


--
-- Name: package_reinvests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_reinvests ALTER COLUMN id SET DEFAULT nextval('public.package_reinvests_id_seq'::regclass);


--
-- Name: package_withdraws id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_withdraws ALTER COLUMN id SET DEFAULT nextval('public.package_withdraws_id_seq'::regclass);


--
-- Name: partners id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.partners ALTER COLUMN id SET DEFAULT nextval('public.partners_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: withdraws id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.withdraws ALTER COLUMN id SET DEFAULT nextval('public.withdraws_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: deposit_gifts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deposit_gifts (id, uuid, comment, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: deposits; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deposits (id, uuid, commission, currency, transaction_hash, wallet_address, created_at, updated_at) FROM stdin;
1	DP-rtSkVjRrnp	0.00000000	usdt_trc_20	qwqwq	test	2024-08-25 14:23:35	2024-08-25 14:23:35
2	DP-HpKopqktI2	0.00000000	usdt_trc_20	asdfasfasfasfasdfs	test	2024-08-26 07:40:34	2024-08-26 07:40:34
3	DP-vRtl5POTLK	0.00000000	usdt_trc_20	Ахмет	test	2024-08-26 10:36:44	2024-08-26 10:36:44
4	DP-etX3QE3D9u	0.00000000	usdt_trc_20	Ахмет	test	2024-08-26 10:36:49	2024-08-26 10:36:49
5	DP-v5MLlCIsoD	0.00000000	usdt_trc_20	Ахмет	test	2024-08-26 10:37:18	2024-08-26 10:37:18
6	DP-XfvvyhjlFL	0.00000000	usdt_trc_20	Axmet	test	2024-08-26 10:37:32	2024-08-26 10:37:32
7	DP-sao3osLhmC	0.00000000	usdt_trc_20	Axmet	test	2024-08-26 10:37:33	2024-08-26 10:37:33
8	DP-PXV8wIfaTu	0.00000000	usdt_trc_20	Axmet	test	2024-08-26 10:37:34	2024-08-26 10:37:34
9	DP-rKPiZknwt7	0.00000000	usdt_trc_20	USDT TRC20	test	2024-08-29 04:15:15	2024-08-29 04:15:15
10	DP-hSICZmvz3P	0.00000000	usdt_trc_20	USDT TRC20	test	2024-08-29 04:15:35	2024-08-29 04:15:35
11	DP-P5giw3e75U	0.00000000	usdt_trc_20	USDT TRC20	test	2024-08-29 04:15:47	2024-08-29 04:15:47
12	DP-fEjk9345LA	0.00000000	usdt_trc_20	USDT TRC20	test	2024-08-29 04:16:44	2024-08-29 04:16:44
13	DP-J8DIYZ6qQz	0.00000000	usdt_trc_20	сбп	test	2024-08-30 13:15:54	2024-08-30 13:15:54
14	DP-B3wc3WDiis	0.00000000	usdt_trc_20	83489f1129353b19f3c26cb171b0ce7068aa3c009bc7fa6a7601a66c828d8999	test	2024-08-31 10:11:32	2024-08-31 10:11:32
15	DP-ISkgj96yrq	0.00000000	usdt_trc_20	83489f1129353b19f3c26cb171b0ce7068aa3c009bc7fa6a7601a66c828d8999	test	2024-08-31 10:11:36	2024-08-31 10:11:36
16	DP-LUlGuyBMNC	0.00000000	usdt_trc_20	83489f1129353b19f3c26cb171b0ce7068aa3c009bc7fa6a7601a66c828d8999	test	2024-08-31 10:11:40	2024-08-31 10:11:40
17	DP-0JySoPIycL	0.00000000	usdt_trc_20	83489f1129353b19f3c26cb171b0ce7068aa3c009bc7fa6a7601a66c828d8999	test	2024-08-31 10:11:42	2024-08-31 10:11:42
18	DP-pqW4trsDxB	0.00000000	usdt_trc_20	Перевод 	test	2024-09-01 15:45:04	2024-09-01 15:45:04
19	DP-04PABclZMQ	0.00000000	usdt_trc_20	Перевод 	test	2024-09-01 15:45:10	2024-09-01 15:45:10
20	DP-ZPAZCHj0DM	0.00000000	usdt_trc_20	Перевод	test	2024-09-08 15:09:54	2024-09-08 15:09:54
21	DP-XLUdUcD5Cl	0.00000000	usdt_trc_20	Перевод СБП	test	2024-09-08 17:23:42	2024-09-08 17:23:42
22	DP-FKAUaYqxBp	0.00000000	usdt_trc_20	Перевод СБП	test	2024-09-08 17:23:46	2024-09-08 17:23:46
23	DP-cHjW7gROoG	0.00000000	usdt_trc_20	Перевод СБП	test	2024-09-08 17:23:56	2024-09-08 17:23:56
24	DP-VfApQmxbBB	0.00000000	usdt_trc_20	Хеш	test	2024-09-16 16:58:34	2024-09-16 16:58:34
25	DP-qLnfavdHS8	0.00000000	usdt_trc_20	Перевод	test	2024-09-26 11:37:06	2024-09-26 11:37:06
26	DP-4dAWFEqNEW	0.00000000	usdt_trc_20	Перевод	test	2024-09-26 13:15:27	2024-09-26 13:15:27
27	DP-RgdJgNcu9o	0.00000000	usdt_trc_20	Перевод	test	2024-09-26 13:15:30	2024-09-26 13:15:30
28	DP-o3Uq1y7CGA	0.00000000	usdt_trc_20	Перевод	test	2024-10-13 14:38:29	2024-10-13 14:38:29
29	DP-XgFpThrtCi	0.00000000	usdt_trc_20	Перевод	test	2024-10-13 14:38:32	2024-10-13 14:38:32
30	DP-rmvdt952Tt	0.00000000	usdt_trc_20	Перевод	test	2024-10-13 14:38:36	2024-10-13 14:38:36
31	DP-Prl64QnlnI	0.00000000	usdt_trc_20	перевод СБП	test	2024-10-17 10:06:05	2024-10-17 10:06:05
32	DP-i5aOnZmvGt	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:29	2024-10-17 12:44:29
33	DP-qxEf0YRwX7	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:31	2024-10-17 12:44:31
34	DP-KIYKwjtB8Z	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:34	2024-10-17 12:44:34
35	DP-1Yk4u1xu8Z	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:37	2024-10-17 12:44:37
36	DP-dxWtpF5Nc7	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:38	2024-10-17 12:44:38
37	DP-TBuS58gciA	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:40	2024-10-17 12:44:40
38	DP-Jyx7dzDiCZ	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:41	2024-10-17 12:44:41
39	DP-DJ7EM3qmsy	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:44:55	2024-10-17 12:44:55
40	DP-3qL7VipRwR	0.00000000	usdt_trc_20	Перевод	test	2024-10-17 12:45:01	2024-10-17 12:45:01
41	DP-ibjiHc8y4c	0.00000000	usdt_trc_20	111	test	2024-10-17 12:45:39	2024-10-17 12:45:39
42	DP-tHmviNYPtC	0.00000000	usdt_trc_20	111	test	2024-10-17 12:45:41	2024-10-17 12:45:41
43	DP-VrDg6fyFEV	0.00000000	usdt_trc_20	Перевод	test	2024-10-18 11:26:54	2024-10-18 11:26:54
44	DP-RU1sRvAveb	0.00000000	usdt_trc_20	Перевод	test	2024-10-23 09:32:13	2024-10-23 09:32:13
45	DP-Jg9C4dTpaR	0.00000000	usdt_trc_20	Перевод	test	2024-10-23 09:32:32	2024-10-23 09:32:32
46	DP-5v9tuiQsE2	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:30:47	2024-10-25 09:30:47
47	DP-8HpulY5Nvm	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:03	2024-10-25 09:31:03
48	DP-6iTKyGTV4s	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:05	2024-10-25 09:31:05
49	DP-DRTcDxXcF4	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:06	2024-10-25 09:31:06
50	DP-8kOBPRHrIS	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:08	2024-10-25 09:31:08
51	DP-dFdMcsgffH	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:09	2024-10-25 09:31:09
52	DP-nREvCKzKux	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:21	2024-10-25 09:31:21
53	DP-OU9KlSQMML	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:23	2024-10-25 09:31:23
54	DP-4hIPpgFH8m	0.00000000	usdt_trc_20	перевод	test	2024-10-25 09:31:30	2024-10-25 09:31:30
55	DP-xYRkhZOJbb	0.00000000	usdt_trc_20	1111	test	2024-11-17 12:09:16	2024-11-17 12:09:16
56	DP-X9ntpshfKE	0.00000000	usdt_trc_20	перевод альфа	test	2024-11-21 12:17:59	2024-11-21 12:17:59
57	DP-tTqg0WMFc9	0.00000000	usdt_trc_20	Trxid: перевод альфа	test	2024-11-23 12:34:54	2024-11-23 12:34:54
58	DP-GrSepB0UIK	0.00000000	usdt_trc_20	100000	test	2024-12-09 16:06:42	2024-12-09 16:06:42
59	DP-rhSWGw5n7I	0.00000000	usdt_trc_20	Перевод Сбер	test	2024-12-10 11:07:20	2024-12-10 11:07:20
60	DP-E7XpLL9aqM	0.00000000	usdt_trc_20	123	test	2024-12-23 15:43:11	2024-12-23 15:43:11
61	DP-PQuuRXvAAp	0.00000000	usdt_trc_20	9f18f66c35f1a55f5de6de4e28a1f50017117a9f209b58fda1892d844684c6b2	test	2024-12-29 13:42:59	2024-12-29 13:42:59
62	DP-VRFRSbiTol	0.00000000	usdt_trc_20	Сбер 	test	2024-12-30 15:33:30	2024-12-30 15:33:30
63	DP-qoLDtvif2N	0.00000000	usdt_trc_20	Сбер	test	2024-12-30 15:35:56	2024-12-30 15:35:56
64	DP-wRzT1THkIc	0.00000000	usdt_trc_20	сбер	test	2024-12-30 15:41:01	2024-12-30 15:41:01
65	DP-0hnKYuqt6F	0.00000000	usdt_trc_20	сбер	test	2024-12-30 15:41:58	2024-12-30 15:41:58
66	DP-Ih97h6izLA	0.00000000	usdt_trc_20	+79911141128	test	2025-01-02 17:29:47	2025-01-02 17:29:47
67	DP-2AYR0YtlVo	0.00000000	usdt_trc_20	перевод	test	2025-01-02 17:37:55	2025-01-02 17:37:55
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: itc_packages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.itc_packages (id, uuid, month_profit_percent, created_at, updated_at, type, work_to) FROM stdin;
5	ITC-luUiKG6k2m	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-01-01 14:36:49
6	ITC-8zlKdVTEKb	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-07-29 15:26:39
7	ITC-Z0CMEPUbwC	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-04-06 14:08:00
8	ITC-5Z9isUEMMA	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-01-27 14:08:36
9	ITC-FX6dgjpbdM	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-04-16 11:12:48
11	ITC-lALXYPYWQ7	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-03-28 13:12:57
12	ITC-dHT7IzMEbg	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-08-24 14:38:57
13	ITC-QRO4BEQ6wZ	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2024-01-27 14:39:09
27	ITC-BKrKKpzteK	8.20	2024-08-25 13:02:04	2024-08-25 13:02:04	vip	2025-05-20 19:08:06
39	ITC-0KZCWKi2eL	8.20	2024-08-26 10:58:06	2024-08-26 10:58:06	standard	2025-03-24 10:58:06
41	ITC-2KcILuMvKT	8.20	2024-09-01 16:10:05	2024-09-01 16:10:05	standard	2025-03-30 16:10:05
45	ITC-7rUVFjBAdo	8.20	2024-09-08 20:41:07	2024-09-08 20:41:07	standard	2025-04-06 20:41:07
30	ITC-zr840ljEPc	12.00	2024-08-25 13:02:04	2024-09-12 09:44:37	vip	2025-01-31 09:00:02
32	ITC-flY9k7HKFl	10.00	2024-08-25 13:02:04	2024-09-12 09:45:41	privilege	2025-02-16 19:12:58
31	ITC-p9IWpWM5vc	10.00	2024-08-25 13:02:04	2024-09-12 09:46:31	privilege	2025-02-14 07:32:39
38	ITC-wi4u5b0jdJ	10.00	2024-08-25 13:02:04	2024-09-12 09:47:20	privilege	2025-03-19 08:41:27
29	ITC-gngFgoaoGi	12.00	2024-08-25 13:02:04	2024-09-12 09:48:18	vip	2025-05-30 18:01:52
28	ITC-ncLOsPXpV0	10.00	2024-08-25 13:02:04	2024-09-12 09:52:08	privilege	2025-01-06 17:05:10
48	ITC-SNfUA4zPX9	8.20	2024-09-12 12:07:24	2024-09-12 12:07:24	standard	2025-04-10 12:07:24
35	ITC-NHnbmFh8Ej	12.00	2024-08-25 13:02:04	2024-09-12 09:53:10	vip	2025-04-19 13:14:25
42	ITC-QuCgap9eFI	12.00	2024-09-03 15:43:20	2024-09-12 09:53:23	vip	2025-04-01 15:43:20
33	ITC-ft4j6MrmEp	12.00	2024-08-25 13:02:04	2024-09-12 09:53:37	vip	2025-04-21 17:48:59
34	ITC-lcMghoXuxS	12.00	2024-08-25 13:02:04	2024-09-12 09:53:48	vip	2025-05-14 06:24:01
26	ITC-4lqsfdy1G3	10.00	2024-08-25 13:02:04	2024-09-12 09:54:24	privilege	2025-03-28 15:41:24
37	ITC-3uK8WuMYbf	10.00	2024-08-25 13:02:04	2024-09-12 09:54:54	vip	2025-03-12 16:29:51
20	ITC-1h9KUgir50	8.20	2024-08-25 13:02:04	2024-09-12 09:56:18	standard	2024-08-12 18:03:37
21	ITC-1DHfDk80aX	10.00	2024-08-25 13:02:04	2024-09-12 09:56:56	privilege	2024-10-17 17:41:10
22	ITC-DlaAptpq2c	10.00	2024-08-25 13:02:04	2024-09-12 09:57:15	privilege	2025-03-01 09:55:47
19	ITC-MmPRFIosoQ	8.20	2024-08-25 13:02:04	2024-09-12 09:57:44	standard	2024-10-17 20:01:26
24	ITC-xWYNRrs8yJ	12.00	2024-08-25 13:02:04	2024-09-12 09:58:16	vip	2025-02-24 18:58:17
23	ITC-1e2g490fDv	10.00	2024-08-25 13:02:04	2024-09-12 09:58:29	privilege	2024-05-03 16:03:41
14	ITC-WHqNMXYAxy	10.00	2024-08-25 13:02:04	2024-09-12 09:59:10	privilege	2024-06-03 09:03:50
15	ITC-7ApObtH2pi	10.00	2024-08-25 13:02:04	2024-09-12 09:59:37	privilege	2025-03-11 13:14:39
73	ITC-AySSuDO0gi	10.00	2024-11-12 13:55:30	2024-11-12 18:36:19	privilege	2025-06-10 13:55:30
44	ITC-NK4e9qdvdM	10.00	2024-09-08 17:34:05	2024-10-17 10:23:29	privilege	2025-04-06 17:34:05
18	ITC-D7q0DLwZ4y	8.20	2024-08-25 13:02:04	2024-09-12 10:11:45	privilege	2023-12-25 19:30:31
4	ITC-IzyqsIsBho	10.00	2024-08-25 13:02:04	2024-09-12 10:13:43	privilege	2024-04-15 17:00:04
1	ITC-GQFm3SnCSL	12.00	2024-08-25 13:02:04	2024-09-12 10:14:18	vip	2024-08-25 22:18:45
2	ITC-e8x2GsZeYs	10.00	2024-08-25 13:02:04	2024-09-12 10:14:45	privilege	2024-04-07 22:19:10
72	ITC-MO95F6ARHa	10.00	2024-11-12 13:48:39	2024-11-12 18:37:07	privilege	2025-06-10 13:48:39
10	ITC-FIuyoYe0ta	10.00	2024-08-25 13:02:04	2024-09-12 10:17:30	privilege	2024-01-14 18:50:53
46	ITC-wKyyQSfDUp	8.20	2024-09-12 10:48:18	2024-09-12 10:48:18	standard	2025-04-10 10:48:18
47	ITC-mFhh9aMM9V	8.20	2024-09-12 10:49:38	2024-09-12 10:49:38	standard	2025-04-10 10:49:38
49	ITC-oa4hGuM7UC	10.00	2024-09-12 19:22:17	2024-09-13 06:14:27	privilege	2025-04-10 19:22:17
51	ITC-YsOvuKKHva	8.20	2024-09-16 14:36:28	2024-09-16 14:36:28	standard	2025-04-14 14:36:28
25	ITC-5SKQtT3vyf	8.20	2024-08-25 13:02:04	2024-09-17 09:24:59	standard	2024-08-16 11:42:38
52	ITC-stchngBjHP	8.20	2024-09-18 06:40:40	2024-09-18 06:40:40	standard	2025-04-16 06:40:40
53	ITC-wIwSqATAWE	8.20	2024-09-18 17:36:09	2024-09-18 17:36:09	standard	2025-04-16 17:36:09
54	ITC-ze20g04VB2	8.20	2024-09-26 06:48:56	2024-09-26 06:48:56	standard	2025-04-24 06:48:56
55	ITC-QXGzxMvmKL	10.00	2024-09-26 11:37:47	2024-09-26 11:38:09	privilege	2025-04-24 11:37:47
56	ITC-YyyMDt1mKa	8.20	2024-09-26 13:15:57	2024-09-26 13:15:57	standard	2025-04-24 13:15:57
57	ITC-UYLbRC56Pw	8.20	2024-09-29 17:01:39	2024-09-29 17:01:39	standard	2025-04-27 17:01:39
58	ITC-hKEcezNxWC	8.20	2024-10-01 17:01:02	2024-10-01 17:01:02	standard	2025-04-29 17:01:02
59	ITC-Dcs5Y9nS5L	10.00	2024-10-06 17:08:14	2024-10-06 17:14:01	privilege	2025-05-04 17:08:14
60	ITC-tZg0t4X5xN	8.20	2024-10-07 08:50:25	2024-10-07 08:50:25	standard	2025-05-05 08:50:25
61	ITC-G5Ad3PJneB	10.00	2024-10-13 14:39:01	2024-10-13 15:11:24	privilege	2025-05-11 14:39:01
50	ITC-JGR3FfLxC6	10.00	2024-09-16 13:37:57	2024-10-13 15:21:56	standard	2025-04-14 13:37:57
62	ITC-nhcMdc7OyW	8.20	2024-10-15 16:46:45	2024-10-15 16:46:45	standard	2025-05-13 16:46:45
71	ITC-0zjnsbw0wu	8.20	2024-11-09 12:20:19	2024-11-09 12:20:19	standard	2025-06-07 12:20:19
36	ITC-ncZ6SSLhOc	10.00	2024-08-25 13:02:04	2024-10-15 16:57:07	privilege	2025-02-26 14:28:26
63	ITC-m7i1VBxXNm	8.20	2024-10-16 17:02:18	2024-10-16 17:02:18	standard	2025-05-14 17:02:18
64	ITC-y9rHXdOAhj	8.20	2024-10-17 10:11:39	2024-10-17 10:11:39	standard	2025-05-15 10:11:39
65	ITC-GvO24AQJZE	10.00	2024-10-17 13:05:13	2024-10-17 13:07:08	privilege	2025-05-15 13:05:13
40	ITC-XbiBgsSJkM	10.00	2024-08-31 10:42:04	2024-11-10 07:14:49	standard	2025-03-29 10:42:04
66	ITC-IKyyNjzZE0	10.00	2024-10-18 11:30:48	2024-10-18 12:04:19	privilege	2025-05-16 11:30:48
67	ITC-f55SXSrqrR	8.20	2024-10-21 14:58:49	2024-10-21 14:58:49	standard	2025-05-19 14:58:49
17	ITC-XKDP9jfHOn	11.00	2024-08-25 13:02:04	2024-10-21 14:59:52	vip	2024-09-10 15:51:15
69	ITC-pr2IoJIB0z	8.20	2024-10-24 17:20:25	2024-10-24 17:20:25	standard	2025-05-22 17:20:25
70	ITC-vAwQY8gH2H	10.00	2024-10-25 09:34:18	2024-10-25 09:46:10	privilege	2025-05-23 09:34:18
43	ITC-G5BiAoEKzU	10.00	2024-09-08 10:51:23	2024-11-10 07:34:26	standard	2025-04-06 10:51:23
74	ITC-lykjDQVfnF	10.00	2024-11-15 16:00:59	2024-11-16 13:14:34	privilege	2025-06-13 16:00:59
76	ITC-dI9XrtHs6d	8.20	2024-11-17 15:24:43	2024-11-17 15:24:43	standard	2025-06-15 15:24:43
75	ITC-J7EPLe7ruL	10.00	2024-11-17 12:25:43	2024-11-17 17:49:16	privilege	2025-06-15 12:25:43
77	ITC-3Rh4ox62hn	8.20	2024-11-17 17:52:10	2024-11-17 17:52:10	standard	2025-06-15 17:52:10
78	ITC-lvrnrUsdGJ	8.20	2024-11-20 17:02:32	2024-11-20 17:02:32	standard	2025-06-18 17:02:32
80	ITC-N6BOPMHJ5z	10.00	2024-11-23 12:35:30	2024-11-23 12:36:26	privilege	2025-06-21 12:35:30
68	ITC-Igk7zIiFps	10.00	2024-10-23 09:33:15	2024-12-18 10:29:04	privilege	2025-05-21 09:33:15
81	ITC-oMN46k7kan	8.20	2024-11-28 18:29:22	2024-11-28 18:29:22	standard	2025-06-26 18:29:22
82	ITC-m3MRU2bJ9F	8.20	2024-11-29 11:55:17	2024-11-29 11:55:17	standard	2025-06-27 11:55:17
16	ITC-lWkgBm6M8y	10.00	2024-08-25 13:02:04	2024-12-22 07:46:26	privilege	2024-09-14 11:37:45
79	ITC-FjvE5ODTlu	10.00	2024-11-21 12:22:01	2024-11-29 14:48:39	privilege	2025-06-19 12:22:01
83	ITC-Z692r80YQL	8.20	2024-11-30 15:14:38	2024-11-30 15:14:38	standard	2025-06-28 15:14:38
84	ITC-GgvO41uKHy	8.20	2024-12-01 06:23:36	2024-12-01 06:23:36	standard	2025-06-29 06:23:36
85	ITC-xNQZsYmvwv	8.20	2024-12-01 14:03:35	2024-12-01 14:03:35	standard	2025-06-29 14:03:35
87	ITC-S6dbIk79cE	10.00	2024-12-10 11:31:56	2024-12-10 11:38:53	privilege	2025-07-08 11:31:56
86	ITC-gKLELcDX6G	10.00	2024-12-09 16:09:06	2024-12-21 18:17:17	privilege	2025-07-07 16:09:06
89	ITC-iSV1wESOKD	8.20	2024-12-25 15:07:59	2024-12-25 15:07:59	standard	2025-07-23 15:07:59
90	ITC-urP93T9Md3	8.20	2024-12-29 13:51:48	2024-12-29 13:51:48	standard	2025-07-27 13:51:48
91	ITC-60gEs0t6rc	8.20	2024-12-30 15:44:56	2024-12-30 15:44:56	standard	2025-07-28 15:44:56
92	ITC-m39FWedy91	8.20	2024-12-30 15:45:27	2024-12-30 15:45:27	standard	2025-07-28 15:45:27
93	ITC-2invasa2qG	8.20	2024-12-30 15:46:06	2024-12-30 15:46:06	standard	2025-07-28 15:46:06
94	ITC-21gDyB05pj	8.20	2024-12-30 15:46:52	2024-12-30 15:46:52	standard	2025-07-28 15:46:52
88	ITC-uzsAOBkQp1	10.00	2024-12-23 15:44:08	2024-12-30 18:26:59	vip	2025-07-21 15:44:08
95	ITC-PRsIBpFHlW	8.20	2025-01-02 17:39:43	2025-01-02 17:39:43	standard	2025-07-31 17:39:43
96	ITC-KJkeQhMidw	8.20	2025-01-07 08:27:52	2025-01-07 08:27:52	standard	2025-08-05 08:27:52
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2020_10_04_115514_create_moonshine_roles_table	1
5	2020_10_05_173148_create_moonshine_tables	1
6	2022_12_19_115549_create_moonshine_socialites_table	1
7	2024_07_01_154851_create_transactions_table	1
8	2024_07_01_155936_create_deposits_table	1
9	2024_07_10_112328_create_withdraws_table	1
10	2024_07_16_103239_create_itc_packages_table	1
11	2024_07_16_123540_create_package_profits	1
12	2024_07_17_123901_create_package_profit_withdraws	1
13	2024_07_17_130428_create_package_profit_reinvests_table	1
14	2024_07_17_142019_create_package_reinvests_table	1
15	2024_07_18_121809_create_package_withdraws_table	1
16	2024_07_19_062413_create_deposit_gifts_table	1
17	2024_08_20_065218_update_itc_package_table	1
18	9999_12_20_173629_create_notifications_table	1
19	2024_09_04_152310_create_partners_table	2
20	2024_09_12_133636_add_banned_at_users_table	3
21	2024_10_24_114459_add_rank_to_users_table	4
\.


--
-- Data for Name: moonshine_socialites; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.moonshine_socialites (id, moonshine_user_id, driver, identity, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: moonshine_user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.moonshine_user_roles (id, name, created_at, updated_at) FROM stdin;
1	Admin	2024-08-25 11:48:34	2024-08-25 11:48:34
\.


--
-- Data for Name: moonshine_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.moonshine_users (id, moonshine_user_role_id, email, password, name, avatar, remember_token, created_at, updated_at) FROM stdin;
1	1	admin@itcapital.top	$2y$12$QKO5t/UhZ33MPpPBhNt9nem/5IY3YSW/vzwkilQ0YOK5mUMNq9gI.	admin@itcapital.top		kBI99jVfjIel7xsKxMfw9pyd4KbnvfcROsPoCbIDVyAClCxI2qS0WHQn14bb	2024-08-29 11:31:41	2024-09-25 15:45:48
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
b69dd4c5-2004-440e-a12c-a16daddb47d4	MoonShine\\Notifications\\MoonShineDatabaseNotification	MoonShine\\Models\\MoonshineUser	1	{"message":"File exported","button":{"link":"https:\\/\\/itcapital.top\\/storage\\/user-resource.xlsx","label":"Download"},"color":null}	2024-10-10 13:20:40	2024-10-03 08:21:05	2024-10-10 13:20:40
e2cc7573-f319-412c-9db1-785cb6b3a58f	MoonShine\\Notifications\\MoonShineDatabaseNotification	MoonShine\\Models\\MoonshineUser	1	{"message":"File exported","button":{"link":"https:\\/\\/itcapital.top\\/storage\\/user-resource.xlsx","label":"Download"},"color":null}	\N	2024-12-23 07:48:44	2024-12-23 07:48:44
\.


--
-- Data for Name: package_profit_reinvests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_profit_reinvests (id, uuid, package_uuid, amount, created_at, updated_at) FROM stdin;
1	PP-GZ7CvXqXvN	ITC-GQFm3SnCSL	182.34900000	2024-08-25 13:02:04	2024-08-25 13:02:04
2	PP-4NcETkDgm0	ITC-e8x2GsZeYs	152.78820000	2024-08-25 13:02:04	2024-08-25 13:02:04
3	PP-MiiSSJXY5L	ITC-9qlOiI2JIo	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
4	PP-JqGdEXmBtf	ITC-IzyqsIsBho	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
5	PP-eZ0GA4cxKB	ITC-luUiKG6k2m	34.22730000	2024-08-25 13:02:04	2024-08-25 13:02:04
6	PP-Xj1eJaY4s6	ITC-8zlKdVTEKb	74.68800000	2024-08-25 13:02:04	2024-08-25 13:02:04
7	PP-2HbsMG3qoE	ITC-Z0CMEPUbwC	38.44800000	2024-08-25 13:02:04	2024-08-25 13:02:04
8	PP-flLnBlNZpD	ITC-5Z9isUEMMA	15.76360000	2024-08-25 13:02:04	2024-08-25 13:02:04
9	PP-HMigUrGZDq	ITC-FX6dgjpbdM	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
10	PP-maaxjMqBZB	ITC-FIuyoYe0ta	16.66500000	2024-08-25 13:02:04	2024-08-25 13:02:04
11	PP-LAjF9piFRl	ITC-lALXYPYWQ7	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
12	PP-Q1YsOkbbPQ	ITC-dHT7IzMEbg	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
13	PP-5EfVhxb76B	ITC-QRO4BEQ6wZ	34.03350000	2024-08-25 13:02:04	2024-08-25 13:02:04
14	PP-szCCiHVte2	ITC-WHqNMXYAxy	347.88040000	2024-08-25 13:02:04	2024-08-25 13:02:04
15	PP-VdZXu3djYQ	ITC-7ApObtH2pi	143.61470000	2024-08-25 13:02:04	2024-08-25 13:02:04
16	PP-Gx2DdolVWc	ITC-lWkgBm6M8y	944.99590000	2024-08-25 13:02:04	2024-08-25 13:02:04
17	PP-SjaN5ZvTie	ITC-XKDP9jfHOn	8032.99000000	2024-08-25 13:02:04	2024-08-25 13:02:04
18	PP-9hRSeUsppY	ITC-D7q0DLwZ4y	1178.29980000	2024-08-25 13:02:04	2024-08-25 13:02:04
19	PP-CR8LfSulQb	ITC-MmPRFIosoQ	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
20	PP-kG0Z0479Cd	ITC-1h9KUgir50	80.48420000	2024-08-25 13:02:04	2024-08-25 13:02:04
21	PP-L5KzpPMLhx	ITC-1DHfDk80aX	113.43750000	2024-08-25 13:02:04	2024-08-25 13:02:04
22	PP-hkM2o1IfJi	ITC-DlaAptpq2c	12.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
23	PP-Vl2u9P7bAl	ITC-1e2g490fDv	211.90610000	2024-08-25 13:02:04	2024-08-25 13:02:04
24	PP-clFBEU7gYK	ITC-xWYNRrs8yJ	28.44000000	2024-08-25 13:02:04	2024-08-25 13:02:04
25	PP-NdgIusJUKH	ITC-5SKQtT3vyf	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
26	PP-BLeQ93yKSi	ITC-4lqsfdy1G3	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
27	PP-UtQcZu5M6N	ITC-BKrKKpzteK	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
28	PP-ANaXPOAo15	ITC-ncLOsPXpV0	116.95920000	2024-08-25 13:02:04	2024-08-25 13:02:04
29	PP-35rO2CGbo2	ITC-gngFgoaoGi	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
30	PP-nkkJDlZYu3	ITC-zr840ljEPc	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
31	PP-wWE1lFfoxa	ITC-p9IWpWM5vc	100.74870000	2024-08-25 13:02:04	2024-08-25 13:02:04
32	PP-Fx2JV41p0x	ITC-flY9k7HKFl	50.37450000	2024-08-25 13:02:04	2024-08-25 13:02:04
33	PP-55S38ZV95o	ITC-ft4j6MrmEp	29.59410000	2024-08-25 13:02:04	2024-08-25 13:02:04
34	PP-eU0JfQlfr5	ITC-lcMghoXuxS	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
35	PP-MegCreoG0R	ITC-NHnbmFh8Ej	599.41530000	2024-08-25 13:02:04	2024-08-25 13:02:04
36	PP-kGgKMaJCVd	ITC-ncZ6SSLhOc	54.18630000	2024-08-25 13:02:04	2024-08-25 13:02:04
37	PP-8A5MdRfjKK	ITC-3uK8WuMYbf	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
38	PP-dZFvlMz9jD	ITC-wi4u5b0jdJ	0.00000000	2024-08-25 13:02:04	2024-08-25 13:02:04
39	PPR-ddheepLuq7	ITC-D7q0DLwZ4y	51.87680000	2024-08-25 16:29:23	2024-08-25 16:29:23
40	PPR-KFCD9lWauF	ITC-p9IWpWM5vc	26.12740000	2024-08-25 16:33:46	2024-08-25 16:33:46
41	PPR-7xp6xJLVi2	ITC-flY9k7HKFl	13.06370000	2024-08-25 16:37:44	2024-08-25 16:37:44
42	PPR-Xi1cFg8H32	ITC-ncZ6SSLhOc	27.22800000	2024-08-25 16:48:02	2024-08-25 16:48:02
43	PPR-ehQKr41Nzy	ITC-xWYNRrs8yJ	28.13240000	2024-08-25 18:04:52	2024-08-25 18:04:52
44	PPR-R2ooX3NJTM	ITC-DlaAptpq2c	11.87030000	2024-08-25 18:11:56	2024-08-25 18:11:56
45	PPR-gdrRbuowXx	ITC-wi4u5b0jdJ	25.00000000	2024-08-28 10:31:03	2024-08-28 10:31:03
46	PPR-zPJkRoQZth	ITC-wi4u5b0jdJ	1.50333150	2024-08-30 14:16:17	2024-08-30 14:16:17
47	PPR-yb5anwX9a8	ITC-lWkgBm6M8y	1.12613196	2024-08-30 21:42:00	2024-08-30 21:42:00
48	PPR-i6yDhZSCG7	ITC-lWkgBm6M8y	6.75679176	2024-08-31 19:44:25	2024-08-31 19:44:25
49	PPR-AYT0yEaEaM	ITC-GQFm3SnCSL	19.13331000	2024-09-01 11:34:12	2024-09-01 11:34:12
50	PPR-ay8vhyJRAL	ITC-e8x2GsZeYs	19.34377641	2024-09-01 11:34:20	2024-09-01 11:34:20
51	PPR-PhcYxg9AYs	ITC-wi4u5b0jdJ	9.01998900	2024-09-01 14:56:30	2024-09-01 14:56:30
52	PPR-1wqQ0jRZqS	ITC-D7q0DLwZ4y	21.04664100	2024-09-01 17:10:38	2024-09-01 17:10:38
53	PPR-LCpfRnoMAO	ITC-p9IWpWM5vc	19.13331000	2024-09-01 17:16:10	2024-09-01 17:16:10
54	PPR-wUXUuDLo1a	ITC-ncZ6SSLhOc	21.25710741	2024-09-01 17:18:27	2024-09-01 17:18:27
55	PPR-Jhg6bWtE3z	ITC-flY9k7HKFl	9.56665500	2024-09-01 17:19:40	2024-09-01 17:19:40
56	PPR-akOGxs1kMa	ITC-1h9KUgir50	4.07539503	2024-09-01 17:36:21	2024-09-01 17:36:21
57	PPR-OkhdryPQbg	ITC-2KcILuMvKT	1.85161200	2024-09-04 20:01:33	2024-09-04 20:01:33
58	PPR-nL2O45Ksxu	ITC-7ApObtH2pi	9.13982902	2024-09-05 03:01:13	2024-09-05 03:01:13
59	PPR-hsUHVClBVP	ITC-lWkgBm6M8y	32.34849291	2024-09-06 21:51:50	2024-09-06 21:51:50
60	PPR-vDJkOZeuA8	ITC-ncZ6SSLhOc	28.76478134	2024-09-07 09:04:22	2024-09-07 09:04:22
61	PPR-LE6AmXFCcQ	ITC-flY9k7HKFl	13.58057812	2024-09-07 09:05:20	2024-09-07 09:05:20
62	PPR-vsiQZMRZ6T	ITC-p9IWpWM5vc	27.16114913	2024-09-07 09:06:01	2024-09-07 09:06:01
63	PPR-Kh6Qdp0BN3	ITC-D7q0DLwZ4y	55.72548055	2024-09-07 09:07:17	2024-09-07 09:07:17
64	PPR-0k2q47sCxW	ITC-2KcILuMvKT	0.52805107	2024-09-07 15:35:28	2024-09-07 15:35:28
65	PPR-s5xViDDRfv	ITC-WHqNMXYAxy	23.01048063	2024-09-08 07:04:04	2024-09-08 07:04:04
66	PPR-SeN0WWDyBi	ITC-7ApObtH2pi	2.60653769	2024-09-08 07:04:06	2024-09-08 07:04:06
67	PPR-waQn1GOWbm	ITC-XbiBgsSJkM	23.70063360	2024-09-08 07:04:10	2024-09-08 07:04:10
68	PPR-lNyiDBCEHr	ITC-FIuyoYe0ta	12.24528786	2024-09-08 13:16:16	2024-09-08 13:16:16
69	PPR-ND1dRTC0Ze	ITC-1h9KUgir50	7.05235094	2024-09-08 13:19:17	2024-09-08 13:19:17
70	PPR-dPyT49Ulhy	ITC-0KZCWKi2eL	1.03690272	2024-09-09 05:10:24	2024-09-09 05:10:24
71	PPR-m3de9iU42E	ITC-wi4u5b0jdJ	13.87727369	2024-09-09 09:38:06	2024-09-09 09:38:06
72	PPR-CwCt3ARMQk	ITC-lWkgBm6M8y	25.87122866	2024-09-09 11:38:29	2024-09-09 11:38:29
73	PPR-xSvjktSTod	ITC-wi4u5b0jdJ	11.09857333	2024-09-09 14:25:44	2024-09-09 14:25:44
74	PPR-207k0SawKH	ITC-7rUVFjBAdo	7.40644800	2024-09-11 05:02:21	2024-09-11 05:02:21
75	PPR-occslGE6Jb	ITC-2KcILuMvKT	1.89567413	2024-09-11 05:02:26	2024-09-11 05:02:26
76	PPR-DvMfPBYnHw	ITC-D7q0DLwZ4y	44.56735136	2024-09-15 09:22:47	2024-09-15 09:22:47
77	PPR-w1z4nCA4OV	ITC-p9IWpWM5vc	21.72256686	2024-09-15 09:26:04	2024-09-15 09:26:04
78	PPR-d557GIvTYx	ITC-flY9k7HKFl	10.86128627	2024-09-15 09:29:43	2024-09-15 09:29:43
79	PPR-GDVbGD8K3E	ITC-ncZ6SSLhOc	23.00509756	2024-09-15 09:31:34	2024-09-15 09:31:34
80	PPR-S5Uapfl7bd	ITC-WHqNMXYAxy	18.40300282	2024-09-15 12:00:46	2024-09-15 12:00:46
81	PPR-Mg8uqCYwYa	ITC-7ApObtH2pi	9.35732616	2024-09-15 12:00:49	2024-09-15 12:00:49
82	PPR-K4YOTyueae	ITC-XbiBgsSJkM	18.95496378	2024-09-15 12:00:52	2024-09-15 12:00:52
83	PPR-CFrJaOW1j4	ITC-1h9KUgir50	5.64023135	2024-09-15 15:45:13	2024-09-15 15:45:13
84	PPR-QCWWVF7Lpb	ITC-GQFm3SnCSL	22.24679062	2024-09-15 19:43:55	2024-09-15 19:43:55
85	PPR-DY5Rs05nN1	ITC-e8x2GsZeYs	21.90701365	2024-09-15 19:44:00	2024-09-15 19:44:00
86	PPR-XP1OZenDOM	ITC-0KZCWKi2eL	3.72242342	2024-09-16 06:39:33	2024-09-16 06:39:33
87	PPR-uOgrwFOukc	ITC-wi4u5b0jdJ	13.78548023	2024-09-16 15:13:41	2024-09-16 15:13:41
88	PPR-QTrqUO2dYZ	ITC-2KcILuMvKT	2.20851646	2024-09-16 16:54:04	2024-09-16 16:54:04
89	PPR-K3ZB50N0l9	ITC-lWkgBm6M8y	32.13451862	2024-09-16 21:31:44	2024-09-16 21:31:44
90	PPR-rDNAYvYea6	ITC-0KZCWKi2eL	3.92096109	2024-09-17 09:27:23	2024-09-17 09:27:23
91	PPR-gh2I5BKobY	ITC-SNfUA4zPX9	2.77741800	2024-09-17 12:11:03	2024-09-17 12:11:03
92	PPR-5CkmKbap2J	ITC-NK4e9qdvdM	39.80965800	2024-09-17 20:59:39	2024-09-17 20:59:39
93	PPR-6MKPSGMsUX	ITC-YsOvuKKHva	1.56831536	2024-09-21 20:49:41	2024-09-21 20:49:41
94	PPR-5hevdfs01d	ITC-1h9KUgir50	5.74466655	2024-09-22 14:56:57	2024-09-22 14:56:57
95	PPR-2YJwnGt6Y9	ITC-ncZ6SSLhOc	23.43106271	2024-09-22 16:21:27	2024-09-22 16:21:27
96	PPR-8xsLERtMbZ	ITC-flY9k7HKFl	13.49074721	2024-09-22 16:22:55	2024-09-22 16:22:55
97	PPR-fp2y6K1bhn	ITC-p9IWpWM5vc	26.98148736	2024-09-22 16:27:19	2024-09-22 16:27:19
98	PPR-cINV6AJV8i	ITC-D7q0DLwZ4y	45.39256579	2024-09-22 16:30:32	2024-09-22 16:30:32
99	PPR-5gLK1JLWDT	ITC-0KZCWKi2eL	4.39291829	2024-09-24 11:23:32	2024-09-24 11:23:32
100	PPR-9dmLKfWFfx	ITC-wi4u5b0jdJ	15.50644218	2024-09-24 14:21:29	2024-09-24 14:21:29
101	PPR-6RnRe9STSb	ITC-SNfUA4zPX9	3.11172951	2024-09-24 15:26:56	2024-09-24 15:26:56
102	PPR-AzpWYlXC84	ITC-NK4e9qdvdM	22.70614435	2024-09-24 15:27:34	2024-09-24 15:27:34
103	PPR-jqHQWnXh6x	ITC-lWkgBm6M8y	36.14615135	2024-09-24 18:48:40	2024-09-24 18:48:40
104	PPR-Uyn6S83ASE	ITC-stchngBjHP	2.19034590	2024-09-25 04:47:42	2024-09-25 04:47:42
105	PPR-mV8BbhBdB2	ITC-7rUVFjBAdo	8.29794535	2024-09-25 04:47:47	2024-09-25 04:47:47
106	PPR-qnUIYpfbDP	ITC-2KcILuMvKT	2.47435057	2024-09-25 04:47:51	2024-09-25 04:47:51
107	PPR-5QIclcdZYp	ITC-IzyqsIsBho	87.01557648	2024-09-25 05:49:14	2024-09-25 05:49:14
108	PPR-85SG6V9iFt	ITC-WHqNMXYAxy	25.14410154	2024-09-26 05:24:23	2024-09-26 05:24:23
109	PPR-gyFrgvZ7hQ	ITC-7ApObtH2pi	12.78495479	2024-09-26 05:24:26	2024-09-26 05:24:26
110	PPR-coAucxVxy8	ITC-XbiBgsSJkM	29.62405871	2024-09-26 05:24:27	2024-09-26 05:24:27
111	PPR-TlpVgJiESh	ITC-YsOvuKKHva	2.27239355	2024-09-28 21:01:47	2024-09-28 21:01:47
112	PPR-E7p4ItAJij	ITC-GQFm3SnCSL	36.47504350	2024-09-29 04:35:33	2024-09-29 04:35:33
113	PPR-8DlJytT8ef	ITC-e8x2GsZeYs	29.93164655	2024-09-29 04:35:37	2024-09-29 04:35:37
114	PPR-0p63oHwpbt	ITC-G5BiAoEKzU	60.60651828	2024-09-29 07:39:30	2024-09-29 07:39:30
115	PPR-3VMnAo8l14	ITC-flY9k7HKFl	15.17491506	2024-09-29 16:20:45	2024-09-29 16:20:45
116	PPR-x4id0ZLfpF	ITC-ncZ6SSLhOc	26.25140659	2024-09-29 16:21:58	2024-09-29 16:21:58
117	PPR-g3gNDxODES	ITC-wIwSqATAWE	2.03677320	2024-09-29 16:23:52	2024-09-29 16:23:52
118	PPR-KQ3g8FnBsD	ITC-p9IWpWM5vc	30.34982217	2024-09-29 16:24:08	2024-09-29 16:24:08
119	PPR-82QbovzeIP	ITC-D7q0DLwZ4y	50.85636598	2024-09-29 17:06:31	2024-09-29 17:06:31
120	PPR-Zs5VlqEOik	ITC-1h9KUgir50	6.43613903	2024-09-29 18:33:02	2024-09-29 18:33:02
121	PPR-6iHPZljo9f	ITC-SNfUA4zPX9	2.88646216	2024-09-30 17:08:59	2024-09-30 17:08:59
122	PPR-cPX08MhY6I	ITC-NK4e9qdvdM	21.06237910	2024-09-30 17:09:28	2024-09-30 17:09:28
123	PPR-4zeGTVgfBs	ITC-lWkgBm6M8y	33.67634190	2024-09-30 18:12:33	2024-09-30 18:12:33
124	PPR-htezYNwRgV	ITC-QXGzxMvmKL	96.64526760	2024-10-01 05:05:43	2024-10-01 05:05:43
125	PPR-j7ck2UtCbT	ITC-UYLbRC56Pw	1.85161200	2024-10-01 05:05:55	2024-10-01 05:05:55
126	PPR-riJRRswzlM	ITC-wi4u5b0jdJ	14.44691147	2024-10-01 08:03:50	2024-10-01 08:03:50
127	PPR-KUbU33ZiFJ	ITC-0KZCWKi2eL	4.07490188	2024-10-01 13:43:32	2024-10-01 13:43:32
128	PPR-NlkmXAKUan	ITC-ze20g04VB2	1.85161200	2024-10-01 13:43:34	2024-10-01 13:43:34
129	PPR-YNnDAgh4Iq	ITC-YyyMDt1mKa	1.96270872	2024-10-03 13:09:32	2024-10-03 13:09:32
130	PPR-GTYcWmrSHF	ITC-WHqNMXYAxy	23.42604478	2024-10-06 09:54:18	2024-10-06 09:54:18
131	PPR-Exdq6e6lGp	ITC-7ApObtH2pi	11.91137902	2024-10-06 09:54:21	2024-10-06 09:54:21
132	PPR-TPLDMHDmp9	ITC-XbiBgsSJkM	27.59989356	2024-10-06 09:54:23	2024-10-06 09:54:23
133	PPR-CVf8kDvCjC	ITC-p9IWpWM5vc	28.27606674	2024-10-06 12:16:05	2024-10-06 12:16:05
134	PPR-9Adorm1Rjk	ITC-wIwSqATAWE	1.88932514	2024-10-06 12:16:08	2024-10-06 12:16:08
135	PPR-uOj6QJNTfn	ITC-ncZ6SSLhOc	24.35098927	2024-10-06 12:18:17	2024-10-06 12:18:17
136	PPR-bP2jWSCnCQ	ITC-flY9k7HKFl	14.13803707	2024-10-06 12:19:17	2024-10-06 12:19:17
137	PPR-sbX5WmxZ7J	ITC-XKDP9jfHOn	249.13230630	2024-10-06 12:32:01	2024-10-06 12:32:01
138	PPR-0FBZjLaqZv	ITC-1h9KUgir50	5.97020780	2024-10-06 16:25:11	2024-10-06 16:25:11
139	PPR-hOxOIgKDrq	ITC-lWkgBm6M8y	34.43677627	2024-10-07 06:23:53	2024-10-07 06:23:53
140	PPR-QbxvB161L5	ITC-hKEcezNxWC	2.07380544	2024-10-07 06:23:57	2024-10-07 06:23:57
141	PPR-itr76kNSkY	ITC-wi4u5b0jdJ	14.77313241	2024-10-07 08:51:08	2024-10-07 08:51:08
142	PPR-c0a9sRkwkR	ITC-Dcs5Y9nS5L	3.04839045	2024-10-07 08:51:22	2024-10-07 08:51:22
143	PPR-qklsVon7Zz	ITC-NK4e9qdvdM	21.45237264	2024-10-07 10:22:11	2024-10-07 10:22:11
144	PPR-CWoVyC4eBs	ITC-SNfUA4zPX9	2.93990824	2024-10-07 10:22:45	2024-10-07 10:22:45
145	PPR-1532JukfOU	ITC-YyyMDt1mKa	1.99905047	2024-10-07 10:48:43	2024-10-07 10:48:43
146	PPR-who0WtTSfk	ITC-7rUVFjBAdo	7.69723243	2024-10-07 13:32:10	2024-10-07 13:32:10
147	PPR-HQPCwvxF9f	ITC-2KcILuMvKT	2.29522498	2024-10-07 13:32:15	2024-10-07 13:32:15
148	PPR-JSW1UOQiob	ITC-stchngBjHP	2.03178025	2024-10-07 13:32:23	2024-10-07 13:32:23
149	PPR-F8TBgFhIu9	ITC-QXGzxMvmKL	98.82758249	2024-10-07 15:12:26	2024-10-07 15:12:26
150	PPR-wBvknKmFQk	ITC-UYLbRC56Pw	3.73750867	2024-10-07 15:12:33	2024-10-07 15:12:33
151	PPR-ikNkzwCXsT	ITC-0KZCWKi2eL	4.15035325	2024-10-08 07:57:22	2024-10-08 07:57:22
152	PPR-uE5IlGJsqK	ITC-ze20g04VB2	1.88589667	2024-10-08 07:57:23	2024-10-08 07:57:23
153	PPR-nw7TUmC5Ra	ITC-wIwSqATAWE	1.92430811	2024-10-08 16:57:11	2024-10-08 16:57:11
154	PPR-4SIQpY2txy	ITC-p9IWpWM5vc	28.91455928	2024-10-08 16:57:35	2024-10-08 16:57:35
155	PPR-EbekrvEemy	ITC-flY9k7HKFl	14.45728342	2024-10-08 16:58:37	2024-10-08 16:58:37
156	PPR-N98j59EPNB	ITC-ncZ6SSLhOc	24.80187511	2024-10-08 16:59:38	2024-10-08 16:59:38
157	PPR-MDoJ4INDKY	ITC-XKDP9jfHOn	254.75788070	2024-10-08 17:05:05	2024-10-08 17:05:05
158	PPR-ANoL4p9w7w	ITC-YsOvuKKHva	2.14492047	2024-10-08 17:05:38	2024-10-08 17:05:38
159	PPR-VNmAzShq79	ITC-GQFm3SnCSL	34.14748705	2024-10-08 21:32:56	2024-10-08 21:32:56
160	PPR-GJRvon1sxl	ITC-e8x2GsZeYs	27.88646440	2024-10-08 21:33:01	2024-10-08 21:33:01
161	PPR-J4wbgdzzzD	ITC-1h9KUgir50	6.08075289	2024-10-13 17:45:11	2024-10-13 17:45:11
162	PPR-R5n5uGWAZt	ITC-lWkgBm6M8y	35.21438175	2024-10-14 12:52:28	2024-10-14 12:52:28
163	PPR-5poLNM4Vo4	ITC-hKEcezNxWC	2.11220427	2024-10-14 12:52:33	2024-10-14 12:52:33
164	PPR-ggkWFDR4xP	ITC-QXGzxMvmKL	101.05917552	2024-10-14 13:21:30	2024-10-14 13:21:30
165	PPR-rmbmaiuHGQ	ITC-UYLbRC56Pw	3.80671283	2024-10-14 13:21:34	2024-10-14 13:21:34
166	PPR-6gkXnrflda	ITC-SNfUA4zPX9	2.99434394	2024-10-14 14:29:37	2024-10-14 14:29:37
167	PPR-3zjq4qI95l	ITC-NK4e9qdvdM	21.84958734	2024-10-14 14:29:53	2024-10-14 14:29:53
168	PPR-lL0towJ4aF	ITC-flY9k7HKFl	14.78373857	2024-10-14 19:10:36	2024-10-14 19:10:36
169	PPR-UbZrf2JZTY	ITC-wIwSqATAWE	1.95993883	2024-10-14 19:11:22	2024-10-14 19:11:22
170	PPR-UwlQi2XxQt	ITC-p9IWpWM5vc	29.56746940	2024-10-14 19:11:50	2024-10-14 19:11:50
171	PPR-rcdhdO1Mtr	ITC-ncZ6SSLhOc	25.26110961	2024-10-14 19:13:09	2024-10-14 19:13:09
172	PPR-OdPKUPFTUD	ITC-YsOvuKKHva	2.18463607	2024-10-14 20:01:33	2024-10-14 20:01:33
173	PPR-75ljTAQHPb	ITC-0KZCWKi2eL	4.22720169	2024-10-15 12:53:52	2024-10-15 12:53:52
174	PPR-LrIaw1ZkU2	ITC-ze20g04VB2	1.92081616	2024-10-15 12:53:54	2024-10-15 12:53:54
175	PPR-CwoTb5XQSz	ITC-D7q0DLwZ4y	47.17472256	2024-10-16 14:32:57	2024-10-16 14:32:57
176	PPR-Qnk6uh5vFd	ITC-XKDP9jfHOn	260.51048433	2024-10-16 14:33:20	2024-10-16 14:33:20
177	PPR-rgoXWRykWP	ITC-IzyqsIsBho	60.08821806	2024-10-17 09:21:10	2024-10-17 09:21:10
178	PPR-6sokLlDzNp	ITC-tZg0t4X5xN	1.85161200	2024-10-17 14:19:00	2024-10-17 14:19:00
179	PPR-Xx50u83GgH	ITC-Dcs5Y9nS5L	3.11722515	2024-10-17 14:19:07	2024-10-17 14:19:07
180	PPR-dhnX5hN25O	ITC-G5BiAoEKzU	56.46537059	2024-10-19 05:14:50	2024-10-19 05:14:50
181	PPR-LwSdelJvKP	ITC-1h9KUgir50	6.19334484	2024-10-20 18:18:38	2024-10-20 18:18:38
182	PPR-yHhtAsCwfF	ITC-NK4e9qdvdM	29.73603590	2024-10-21 13:21:49	2024-10-21 13:21:49
183	PPR-o36CGVf8gk	ITC-SNfUA4zPX9	3.04978757	2024-10-21 13:22:05	2024-10-21 13:22:05
184	PPR-bvh90qAS7A	ITC-y9rHXdOAhj	19.18270032	2024-10-21 13:22:11	2024-10-21 13:22:11
185	PPR-uqcSUTIsPZ	ITC-ncZ6SSLhOc	32.61862977	2024-10-21 13:25:16	2024-10-21 13:25:16
186	PPR-WooX6XLJGM	ITC-flY9k7HKFl	15.11756529	2024-10-21 13:30:05	2024-10-21 13:30:05
187	PPR-waJSRP6rzW	ITC-p9IWpWM5vc	30.79963942	2024-10-21 13:34:53	2024-10-21 13:34:53
188	PPR-taPNwDavgv	ITC-wIwSqATAWE	1.99622929	2024-10-21 13:34:56	2024-10-21 13:34:56
189	PPR-6sqsu0pykl	ITC-lWkgBm6M8y	36.00954608	2024-10-21 13:45:41	2024-10-21 13:45:41
190	PPR-JFwBwyWOPY	ITC-hKEcezNxWC	4.00292610	2024-10-21 13:45:44	2024-10-21 13:45:44
191	PPR-E8ZuLt1BT0	ITC-Dcs5Y9nS5L	3.18761418	2024-10-21 14:09:32	2024-10-21 14:09:32
192	PPR-u3V118L2yk	ITC-nhcMdc7OyW	1.85161200	2024-10-21 14:24:08	2024-10-21 14:24:08
193	PPR-c2o1xfawRJ	ITC-QXGzxMvmKL	103.34115941	2024-10-21 19:23:38	2024-10-21 19:23:38
194	PPR-gPjHcGT0ab	ITC-UYLbRC56Pw	3.87719838	2024-10-21 19:23:46	2024-10-21 19:23:46
195	PPR-GWmusjFF8V	ITC-YsOvuKKHva	2.22508706	2024-10-22 05:02:10	2024-10-22 05:02:10
196	PPR-mCkpfkFXss	ITC-IzyqsIsBho	21.38623824	2024-10-22 07:07:16	2024-10-22 07:07:16
197	PPR-xgf6fQfs4U	ITC-0KZCWKi2eL	4.30547307	2024-10-22 09:10:27	2024-10-22 09:10:27
198	PPR-Gp7dEuIWwk	ITC-ze20g04VB2	1.95638222	2024-10-22 09:10:29	2024-10-22 09:10:29
199	PPR-t4bgsXrM3M	ITC-XbiBgsSJkM	28.22311765	2024-10-22 09:18:51	2024-10-22 09:18:51
200	PPR-KZ9Smj7PB6	ITC-7ApObtH2pi	12.18034594	2024-10-22 09:18:54	2024-10-22 09:18:54
201	PPR-ffxdegeRA8	ITC-WHqNMXYAxy	23.95502057	2024-10-22 09:18:56	2024-10-22 09:18:56
202	PPR-q7TJUjNdFu	ITC-tZg0t4X5xN	1.88589667	2024-10-22 10:00:53	2024-10-22 10:00:53
203	PPR-pv7Yhwd2M5	ITC-wi4u5b0jdJ	15.40026835	2024-10-22 10:01:04	2024-10-22 10:01:04
204	PPR-ScUFOWbErd	ITC-YyyMDt1mKa	4.07213026	2024-10-22 12:41:46	2024-10-22 12:41:46
205	PPR-uilSyJ84xw	ITC-m7i1VBxXNm	1.85161200	2024-10-22 14:39:08	2024-10-22 14:39:08
206	PPR-ikkN7lOrs0	ITC-ncLOsPXpV0	30.12077120	2024-10-23 12:01:03	2024-10-23 12:01:03
207	PPR-n8vsoZXMyN	ITC-GQFm3SnCSL	35.07277434	2024-10-27 15:04:51	2024-10-27 15:04:51
208	PPR-QqoAWH96xr	ITC-e8x2GsZeYs	28.51615946	2024-10-27 15:04:55	2024-10-27 15:04:55
209	PPR-b7t7KhnyuD	ITC-1h9KUgir50	6.30802155	2024-10-27 15:34:39	2024-10-27 15:34:39
210	PPR-ehlMIenGap	ITC-SNfUA4zPX9	3.10625780	2024-10-28 11:02:10	2024-10-28 11:02:10
211	PPR-EazORRj2rE	ITC-NK4e9qdvdM	30.40749552	2024-10-28 11:02:22	2024-10-28 11:02:22
212	PPR-th88Q4qGIW	ITC-y9rHXdOAhj	19.53788950	2024-10-28 11:02:29	2024-10-28 11:02:29
213	PPR-qyJWihPHnF	ITC-m7i1VBxXNm	1.88589667	2024-10-28 11:58:43	2024-10-28 11:58:43
214	PPR-5UEwTO3qW1	ITC-ncZ6SSLhOc	33.35518029	2024-10-28 12:02:16	2024-10-28 12:02:16
215	PPR-ZmzUDO5rk3	ITC-wIwSqATAWE	2.03319171	2024-10-28 12:04:24	2024-10-28 12:04:24
216	PPR-Cj7RZCfp4G	ITC-p9IWpWM5vc	31.49511591	2024-10-28 12:07:54	2024-10-28 12:07:54
217	PPR-lTQmNnN3I5	ITC-D7q0DLwZ4y	48.04821538	2024-10-28 12:16:30	2024-10-28 12:16:30
218	PPR-1vjicdE0Zg	ITC-XKDP9jfHOn	293.03220159	2024-10-28 12:25:54	2024-10-28 12:25:54
219	PPR-eeQdBsXE1m	ITC-nhcMdc7OyW	1.88589667	2024-10-28 12:27:26	2024-10-28 12:27:26
220	PPR-kCXjfSpvdu	ITC-f55SXSrqrR	4.07354640	2024-10-28 12:30:29	2024-10-28 12:30:29
221	PPR-7HI5uoSEAy	ITC-QXGzxMvmKL	105.67467203	2024-10-28 12:59:26	2024-10-28 12:59:26
222	PPR-kMmv0GlqJn	ITC-UYLbRC56Pw	3.94898905	2024-10-28 12:59:32	2024-10-28 12:59:32
223	PPR-W7ALJwsfUt	ITC-YsOvuKKHva	2.26628703	2024-10-28 14:33:18	2024-10-28 14:33:18
224	PPR-nff6Ok8H0A	ITC-lWkgBm6M8y	36.82266576	2024-10-28 15:56:13	2024-10-28 15:56:13
225	PPR-X2zrSvLAhX	ITC-hKEcezNxWC	4.07704476	2024-10-28 15:56:17	2024-10-28 15:56:17
226	PPR-4OVe7MmPTG	ITC-WHqNMXYAxy	24.49594099	2024-10-29 04:59:10	2024-10-29 04:59:10
227	PPR-cs7tVrJFgW	ITC-7ApObtH2pi	12.45538631	2024-10-29 04:59:13	2024-10-29 04:59:13
228	PPR-0mFIKbmCGS	ITC-XbiBgsSJkM	28.86041456	2024-10-29 04:59:15	2024-10-29 04:59:15
229	PPR-27xpGEatDz	ITC-wi4u5b0jdJ	15.74801673	2024-10-29 09:24:53	2024-10-29 09:24:53
230	PPR-RhnVEjzNVg	ITC-Dcs5Y9nS5L	3.25959265	2024-10-29 09:25:04	2024-10-29 09:25:04
231	PPR-zSSoO4ARSU	ITC-tZg0t4X5xN	1.92081616	2024-10-29 09:25:10	2024-10-29 09:25:10
232	PPR-u8uBVT50vw	ITC-ncLOsPXpV0	15.74053279	2024-10-29 09:37:35	2024-10-29 09:37:35
233	PPR-0Cfb6tnz9B	ITC-YyyMDt1mKa	2.11146518	2024-10-29 14:27:36	2024-10-29 14:27:36
234	PPR-LxnhCMreMa	ITC-IzyqsIsBho	21.86915383	2024-10-30 06:25:45	2024-10-30 06:25:45
235	PPR-5u3iSdmNy3	ITC-Igk7zIiFps	7.55457696	2024-10-31 06:10:21	2024-10-31 06:10:21
236	PPR-6wWx31Ekpv	ITC-0KZCWKi2eL	4.38519372	2024-10-31 09:59:08	2024-10-31 09:59:08
237	PPR-7mXxMtJBHP	ITC-ze20g04VB2	1.99260683	2024-10-31 09:59:10	2024-10-31 09:59:10
238	PPR-UUNArPhShO	ITC-flY9k7HKFl	15.45893004	2024-10-31 19:02:37	2024-10-31 19:02:37
239	PPR-gewciBLUSH	ITC-vAwQY8gH2H	157.16146320	2024-11-01 19:29:13	2024-11-01 19:29:13
240	PPR-EJV5OwDZaO	ITC-1h9KUgir50	6.42482164	2024-11-03 17:12:59	2024-11-03 17:12:59
241	PPR-8pLzZ4gSjJ	ITC-IzyqsIsBho	22.36297397	2024-11-05 05:46:45	2024-11-05 05:46:45
242	PPR-IO6PuySi2W	ITC-wi4u5b0jdJ	16.10361750	2024-11-05 06:34:37	2024-11-05 06:34:37
243	PPR-GlafrmkBFf	ITC-Dcs5Y9nS5L	3.33319643	2024-11-05 06:34:45	2024-11-05 06:34:45
244	PPR-yvAi7l6NW8	ITC-tZg0t4X5xN	1.95638222	2024-11-05 06:34:50	2024-11-05 06:34:50
245	PPR-VLqbvHMdRI	ITC-vAwQY8gH2H	160.71027434	2024-11-05 08:44:52	2024-11-05 08:44:52
246	PPR-ppCy5JQOeN	ITC-p9IWpWM5vc	32.20629673	2024-11-05 09:01:16	2024-11-05 09:01:16
247	PPR-BBrzfS1XcL	ITC-wIwSqATAWE	2.07083853	2024-11-05 09:01:20	2024-11-05 09:01:20
248	PPR-84zbcUYl0N	ITC-flY9k7HKFl	15.80800304	2024-11-05 09:03:33	2024-11-05 09:03:33
249	PPR-VRNHSaCVLa	ITC-D7q0DLwZ4y	48.93788190	2024-11-05 09:09:44	2024-11-05 09:09:44
250	PPR-abd9A7HSxm	ITC-XKDP9jfHOn	300.31074933	2024-11-05 09:11:04	2024-11-05 09:11:04
251	PPR-84noYvXoEF	ITC-nhcMdc7OyW	1.92081616	2024-11-05 09:12:14	2024-11-05 09:12:14
252	PPR-nnfeRLTSTV	ITC-f55SXSrqrR	4.14897267	2024-11-05 09:12:57	2024-11-05 09:12:57
253	PPR-UVQsLKjGxn	ITC-lWkgBm6M8y	37.65414622	2024-11-05 14:20:49	2024-11-05 14:20:49
254	PPR-kpPCfuoUIE	ITC-hKEcezNxWC	4.15253581	2024-11-05 14:20:53	2024-11-05 14:20:53
255	PPR-mOWXI1LVff	ITC-QXGzxMvmKL	108.06087693	2024-11-05 17:00:08	2024-11-05 17:00:08
256	PPR-xwtFRIcYra	ITC-UYLbRC56Pw	4.02210901	2024-11-05 17:00:14	2024-11-05 17:00:14
257	PPR-i5ksqMRfoE	ITC-m7i1VBxXNm	1.92081616	2024-11-05 18:05:41	2024-11-05 18:05:41
258	PPR-SFvlPw4Ooz	ITC-YsOvuKKHva	2.30824988	2024-11-06 02:59:36	2024-11-06 02:59:36
259	PPR-OdfXYk4snt	ITC-ncLOsPXpV0	16.09596457	2024-11-07 09:34:02	2024-11-07 09:34:02
260	PPR-t9gM4S7OIo	ITC-0KZCWKi2eL	4.46639050	2024-11-08 08:44:31	2024-11-08 08:44:31
261	PPR-foaW8cUyLt	ITC-ze20g04VB2	2.02950218	2024-11-08 08:44:33	2024-11-08 08:44:33
262	PPR-p21dcwVdpN	ITC-Igk7zIiFps	7.69445841	2024-11-08 15:37:06	2024-11-08 15:37:06
263	PPR-ypOHbnin0r	ITC-GQFm3SnCSL	36.02313394	2024-11-09 06:01:21	2024-11-09 06:01:21
264	PPR-l56JK5fG28	ITC-e8x2GsZeYs	29.16007344	2024-11-09 06:01:24	2024-11-09 06:01:24
265	PPR-8ylrmMbJlF	ITC-1h9KUgir50	6.54378441	2024-11-10 13:01:51	2024-11-10 13:01:51
266	PPR-zwvUGy2CkW	ITC-0KZCWKi2eL	4.54909072	2024-11-11 08:21:42	2024-11-11 08:21:42
267	PPR-DM4q7uGSKf	ITC-ze20g04VB2	2.06708068	2024-11-11 08:21:44	2024-11-11 08:21:44
268	PPR-kfXgwaMhyC	ITC-lWkgBm6M8y	38.50440207	2024-11-11 09:21:21	2024-11-11 09:21:21
269	PPR-4z0U3PYj41	ITC-hKEcezNxWC	4.22942466	2024-11-11 09:21:27	2024-11-11 09:21:27
270	PPR-d1Gm9FRQcp	ITC-tZg0t4X5xN	1.99260683	2024-11-11 10:06:15	2024-11-11 10:06:15
271	PPR-Zru2Nu96rm	ITC-Dcs5Y9nS5L	3.40846224	2024-11-11 10:06:19	2024-11-11 10:06:19
272	PPR-ggBTpPK22s	ITC-wi4u5b0jdJ	16.46724797	2024-11-11 10:06:33	2024-11-11 10:06:33
273	PPR-tloUtQ4tqg	ITC-YsOvuKKHva	2.35098971	2024-11-11 13:12:11	2024-11-11 13:12:11
274	PPR-Vagg0DKOk9	ITC-YyyMDt1mKa	4.30112264	2024-11-11 16:45:27	2024-11-11 16:45:27
275	PPR-uqAp5IIUP3	ITC-m7i1VBxXNm	1.95638222	2024-11-11 16:55:48	2024-11-11 16:55:48
276	PPR-rDmSCHQa0g	ITC-ncLOsPXpV0	16.45942223	2024-11-11 19:57:39	2024-11-11 19:57:39
277	PPR-cU1tgfvZY0	ITC-vAwQY8gH2H	164.33922001	2024-11-11 20:19:56	2024-11-11 20:19:56
278	PPR-jo00j7aiME	ITC-0zjnsbw0wu	1.85161200	2024-11-11 20:20:08	2024-11-11 20:20:08
279	PPR-Xc4fdRSK8q	ITC-IzyqsIsBho	22.86794491	2024-11-12 05:37:32	2024-11-12 05:37:32
280	PPR-K5wDVFS5G8	ITC-QXGzxMvmKL	110.50096393	2024-11-12 17:37:18	2024-11-12 17:37:18
281	PPR-AZVGf4o9Oc	ITC-UYLbRC56Pw	4.09658286	2024-11-12 17:37:22	2024-11-12 17:37:22
282	PPR-LDfP9CAhyg	ITC-XbiBgsSJkM	27.25403505	2024-11-13 03:35:44	2024-11-13 03:35:44
283	PPR-FMmaQykVkC	ITC-7ApObtH2pi	12.73663728	2024-11-13 03:35:50	2024-11-13 03:35:50
284	PPR-6vBuj74Kto	ITC-WHqNMXYAxy	25.04907575	2024-11-13 03:35:52	2024-11-13 03:35:52
285	PPR-4XAON5Squr	ITC-ncZ6SSLhOc	68.21672522	2024-11-14 20:43:36	2024-11-14 20:43:36
286	PPR-GRKDM9jRTj	ITC-flY9k7HKFl	16.16495834	2024-11-14 20:45:12	2024-11-14 20:45:12
287	PPR-y6oPVgwM6R	ITC-p9IWpWM5vc	32.93353649	2024-11-14 20:46:25	2024-11-14 20:46:25
288	PPR-RypTt0j1rT	ITC-wIwSqATAWE	2.10918243	2024-11-14 20:46:48	2024-11-14 20:46:48
289	PPR-wH2pebkvAE	ITC-nhcMdc7OyW	1.95638222	2024-11-14 20:53:14	2024-11-14 20:53:14
290	PPR-nCFDYhIpP1	ITC-f55SXSrqrR	4.22579555	2024-11-14 20:53:51	2024-11-14 20:53:51
291	PPR-TYrMAm8Fij	ITC-XKDP9jfHOn	307.77008694	2024-11-14 20:55:16	2024-11-14 20:55:16
292	PPR-2Pu9aAxk3z	ITC-D7q0DLwZ4y	49.84402160	2024-11-14 21:08:27	2024-11-14 21:08:27
293	PPR-JFWwAz9Smq	ITC-Igk7zIiFps	7.83692993	2024-11-16 05:23:51	2024-11-16 05:23:51
294	PPR-tZqVFnfLga	ITC-1h9KUgir50	6.66494990	2024-11-17 11:35:21	2024-11-17 11:35:21
295	PPR-Ko75oxWqGG	ITC-AySSuDO0gi	25.44841509	2024-11-18 04:33:10	2024-11-18 04:33:10
296	PPR-gwPkfNLAXG	ITC-MO95F6ARHa	25.44841509	2024-11-18 04:37:35	2024-11-18 04:37:35
297	PPR-EIDgvGq68w	ITC-IzyqsIsBho	23.38431843	2024-11-18 05:30:33	2024-11-18 05:30:33
298	PPR-Yn8pYYNGtr	ITC-nhcMdc7OyW	1.99260683	2024-11-18 07:46:32	2024-11-18 07:46:32
299	PPR-D8Hvm0Vs0Z	ITC-f55SXSrqrR	4.30404089	2024-11-18 07:46:57	2024-11-18 07:46:57
300	PPR-vHsTEqHgWk	ITC-XKDP9jfHOn	315.41470504	2024-11-18 07:48:04	2024-11-18 07:48:04
301	PPR-zTCZADt20D	ITC-D7q0DLwZ4y	50.76693948	2024-11-18 07:53:59	2024-11-18 07:53:59
302	PPR-qRdZfB6DOT	ITC-p9IWpWM5vc	33.67719781	2024-11-18 07:59:02	2024-11-18 07:59:02
303	PPR-bDWbfNVSq2	ITC-wIwSqATAWE	2.14823630	2024-11-18 08:02:44	2024-11-18 08:02:44
304	PPR-ckEJgZtsG4	ITC-flY9k7HKFl	16.52997393	2024-11-18 08:06:09	2024-11-18 08:06:09
305	PPR-T071QTIIAw	ITC-ncZ6SSLhOc	35.64874197	2024-11-18 08:08:19	2024-11-18 08:08:19
306	PPR-udbASCn7vN	ITC-YyyMDt1mKa	2.23020143	2024-11-18 08:48:37	2024-11-18 08:48:37
307	PPR-EweUaFrAAQ	ITC-0KZCWKi2eL	4.63332223	2024-11-18 13:39:42	2024-11-18 13:39:42
308	PPR-UFPvJv1pGq	ITC-ze20g04VB2	2.10535500	2024-11-18 13:54:52	2024-11-18 13:54:52
309	PPR-ZzEutTgPq5	ITC-0zjnsbw0wu	1.88589667	2024-11-18 14:05:13	2024-11-18 14:05:13
310	PPR-3saZ259JUL	ITC-vAwQY8gH2H	168.05010970	2024-11-18 14:05:42	2024-11-18 14:05:42
311	PPR-PSPs7K7fPF	ITC-m7i1VBxXNm	1.99260683	2024-11-18 14:27:50	2024-11-18 14:27:50
312	PPR-FUPqyFX6NA	ITC-ncLOsPXpV0	16.83108702	2024-11-19 05:17:25	2024-11-19 05:17:25
313	PPR-AdBVqe0jOK	ITC-wi4u5b0jdJ	16.83908946	2024-11-19 05:47:49	2024-11-19 05:47:49
314	PPR-VqQu3CqrLH	ITC-Dcs5Y9nS5L	3.48542760	2024-11-19 05:48:00	2024-11-19 05:48:00
315	PPR-Hz7gqNC5w9	ITC-tZg0t4X5xN	2.02950218	2024-11-19 05:48:08	2024-11-19 05:48:08
316	PPR-JgWwCdUMGk	ITC-YsOvuKKHva	2.39452092	2024-11-20 12:48:44	2024-11-20 12:48:44
317	PPR-lJyqAUPIz0	ITC-Igk7zIiFps	7.98203946	2024-11-20 16:39:28	2024-11-20 16:39:28
318	PPR-cwN6gDno44	ITC-UYLbRC56Pw	4.17243568	2024-11-24 05:05:44	2024-11-24 05:05:44
319	PPR-4XaifUZDgu	ITC-GQFm3SnCSL	36.99924523	2024-11-24 06:22:27	2024-11-24 06:22:27
320	PPR-zxSfIp1QW7	ITC-e8x2GsZeYs	29.81852744	2024-11-24 06:22:30	2024-11-24 06:22:30
321	PPR-QnYBKYkWAT	ITC-1h9KUgir50	6.78835892	2024-11-24 13:10:34	2024-11-24 13:10:34
322	PPR-qoH8VoKp9X	ITC-FjvE5ODTlu	18.14579760	2024-11-25 09:26:24	2024-11-25 09:26:24
323	PPR-ootdLzf9hs	ITC-YsOvuKKHva	2.43885815	2024-11-25 13:18:41	2024-11-25 13:18:41
324	PPR-9h6szFGzcT	ITC-wIwSqATAWE	2.18801330	2024-11-25 13:46:45	2024-11-25 13:46:45
325	PPR-viQ9WMbGfN	ITC-p9IWpWM5vc	34.43765150	2024-11-25 13:46:54	2024-11-25 13:46:54
326	PPR-S19URflTRi	ITC-flY9k7HKFl	16.90323182	2024-11-25 13:51:35	2024-11-25 13:51:35
327	PPR-tASOgbPW5A	ITC-ncZ6SSLhOc	36.45371445	2024-11-25 13:55:00	2024-11-25 13:55:00
328	PPR-SVcdyE1vvb	ITC-f55SXSrqrR	4.38373502	2024-11-25 14:00:45	2024-11-25 14:00:45
329	PPR-Z3vd55oMFc	ITC-nhcMdc7OyW	2.02950218	2024-11-25 14:01:13	2024-11-25 14:01:13
330	PPR-xc2nWp0Yrd	ITC-D7q0DLwZ4y	51.70694623	2024-11-25 14:08:10	2024-11-25 14:08:10
331	PPR-VqDAGQCT7A	ITC-ncLOsPXpV0	17.21114424	2024-11-25 14:56:22	2024-11-25 14:56:22
332	PPR-a6HQoTNf30	ITC-lvrnrUsdGJ	9.07289880	2024-11-25 14:59:12	2024-11-25 14:59:12
333	PPR-y9MxrKboCH	ITC-YyyMDt1mKa	2.27149610	2024-11-25 16:44:32	2024-11-25 16:44:32
334	PPR-bnCFV4a0Do	ITC-QXGzxMvmKL	112.99614973	2024-11-25 17:21:53	2024-11-25 17:21:53
335	PPR-UrFOObyqcm	ITC-UYLbRC56Pw	4.24969300	2024-11-25 17:22:01	2024-11-25 17:22:01
336	PPR-PHPNnGBgBP	ITC-AySSuDO0gi	26.02305735	2024-11-25 18:04:09	2024-11-25 18:04:09
337	PPR-EdCl1cAhQq	ITC-MO95F6ARHa	26.02305735	2024-11-25 18:04:23	2024-11-25 18:04:23
338	PPR-eubWzSMukB	ITC-IzyqsIsBho	23.91235200	2024-11-26 02:28:04	2024-11-26 02:28:04
339	PPR-strTF6qzyK	ITC-0zjnsbw0wu	1.92081616	2024-11-26 08:30:02	2024-11-26 08:30:02
340	PPR-LbO5qXcgwU	ITC-vAwQY8gH2H	171.84479377	2024-11-26 08:30:22	2024-11-26 08:30:22
341	PPR-Qcbwt9kTzq	ITC-WHqNMXYAxy	25.61470066	2024-11-26 08:44:48	2024-11-26 08:44:48
342	PPR-PwmWJsBghP	ITC-7ApObtH2pi	13.02423908	2024-11-26 08:44:51	2024-11-26 08:44:51
343	PPR-N2JSqh6WWH	ITC-XbiBgsSJkM	27.86944943	2024-11-26 08:45:03	2024-11-26 08:45:03
344	PPR-RE5PPCXqc1	ITC-Igk7zIiFps	9.91444972	2024-11-26 15:28:46	2024-11-26 15:28:46
345	PPR-uLqG5yitEB	ITC-wi4u5b0jdJ	17.21932738	2024-11-26 15:46:05	2024-11-26 15:46:05
346	PPR-wupS20k5qv	ITC-Dcs5Y9nS5L	3.56413089	2024-11-26 15:46:11	2024-11-26 15:46:11
347	PPR-tOVmnm1Hkt	ITC-tZg0t4X5xN	2.06708068	2024-11-26 15:46:16	2024-11-26 15:46:16
348	PPR-0iuHcNPJ3R	ITC-m7i1VBxXNm	2.02950218	2024-11-28 16:19:09	2024-11-28 16:19:09
349	PPR-G6b4NP6FD5	ITC-1h9KUgir50	6.91405298	2024-12-01 14:06:20	2024-12-01 14:06:20
350	PPR-yWZm4dVody	ITC-lWkgBm6M8y	40.55507299	2024-12-02 08:13:02	2024-12-02 08:13:02
351	PPR-LcSvNQX73K	ITC-hKEcezNxWC	4.43696931	2024-12-02 08:13:07	2024-12-02 08:13:07
352	PPR-XhGgq9grAB	ITC-m7i1VBxXNm	2.12909310	2024-12-02 08:57:46	2024-12-02 08:57:46
353	PPR-sqgSfoHfBQ	ITC-YsOvuKKHva	2.55853683	2024-12-02 09:03:35	2024-12-02 09:03:35
354	PPR-9yWlVgbeqY	ITC-QXGzxMvmKL	119.01410886	2024-12-02 10:58:18	2024-12-02 10:58:18
355	PPR-rctZdszpEV	ITC-UYLbRC56Pw	4.45823225	2024-12-02 10:58:29	2024-12-02 10:58:29
356	PPR-cZWRE36c3a	ITC-IzyqsIsBho	25.18587820	2024-12-02 11:29:29	2024-12-02 11:29:29
357	PPR-RjhxT0ZY1W	ITC-vAwQY8gH2H	180.99691928	2024-12-02 12:15:17	2024-12-02 12:15:17
358	PPR-cCrczb65Uv	ITC-0zjnsbw0wu	2.01507369	2024-12-02 12:15:36	2024-12-02 12:15:36
359	PPR-sWGi5TQHtu	ITC-oMN46k7kan	1.90716036	2024-12-02 13:17:25	2024-12-02 13:17:25
360	PPR-QNKf9Htl4V	ITC-MO95F6ARHa	27.40899569	2024-12-02 13:17:39	2024-12-02 13:17:39
361	PPR-fGelBRVRcB	ITC-lvrnrUsdGJ	9.51812049	2024-12-02 13:24:16	2024-12-02 13:24:16
362	PPR-kxyCFH3Wx4	ITC-FjvE5ODTlu	67.98678834	2024-12-02 14:26:52	2024-12-02 14:26:52
363	PPR-puZb4mWCww	ITC-AySSuDO0gi	27.40899569	2024-12-02 15:50:45	2024-12-02 15:50:45
364	PPR-PVz7aYyqwS	ITC-GQFm3SnCSL	39.14186019	2024-12-03 04:18:02	2024-12-03 04:18:02
365	PPR-bclHVLx3pK	ITC-e8x2GsZeYs	31.40660526	2024-12-03 04:18:06	2024-12-03 04:18:06
366	PPR-NcvsNX4AsL	ITC-ncZ6SSLhOc	38.39516966	2024-12-03 05:44:56	2024-12-03 05:44:56
367	PPR-HIa9EWPhkW	ITC-Igk7zIiFps	10.44247438	2024-12-03 05:45:52	2024-12-03 05:45:52
368	PPR-PDtiahtIVb	ITC-p9IWpWM5vc	36.27173504	2024-12-03 05:54:44	2024-12-03 05:54:44
369	PPR-h8EciFxJiU	ITC-wIwSqATAWE	0.38822227	2024-12-03 05:54:55	2024-12-03 05:54:55
370	PPR-X9ZeEQSopl	ITC-XKDP9jfHOn	332.94668191	2024-12-03 05:59:39	2024-12-03 05:59:39
371	PPR-Fh4qSFLWnJ	ITC-nhcMdc7OyW	2.12909310	2024-12-03 06:06:03	2024-12-03 06:06:03
372	PPR-GACdOithaL	ITC-f55SXSrqrR	4.59885193	2024-12-03 06:06:24	2024-12-03 06:06:24
373	PPR-Ce5B8z4DVC	ITC-wi4u5b0jdJ	18.13639587	2024-12-04 08:03:36	2024-12-04 08:03:36
374	PPR-0gbfIoyeiI	ITC-Dcs5Y9nS5L	3.75394970	2024-12-04 08:03:41	2024-12-04 08:03:41
375	PPR-rdS7JqR6Dt	ITC-tZg0t4X5xN	2.16851565	2024-12-04 08:03:46	2024-12-04 08:03:46
376	PPR-VZuzsav6Fd	ITC-Z692r80YQL	1.90716036	2024-12-06 17:13:30	2024-12-06 17:13:30
377	PPR-dY9riwB0b2	ITC-D7q0DLwZ4y	54.24428900	2024-12-06 18:25:11	2024-12-06 18:25:11
378	PPR-NOnCWk6Kqv	ITC-flY9k7HKFl	17.80346566	2024-12-06 18:25:54	2024-12-06 18:25:54
379	PPR-LyXEl7hQ7U	ITC-5SKQtT3vyf	91.44317976	2024-12-06 20:20:02	2024-12-06 20:20:02
380	PPR-MztqtCrQjI	ITC-1h9KUgir50	7.25333665	2024-12-08 18:00:20	2024-12-08 18:00:20
381	PPR-ffHaHCJ0kr	ITC-3Rh4ox62hn	5.61038436	2024-12-08 18:01:21	2024-12-08 18:01:21
382	PPR-CBsy9xgW1W	ITC-IzyqsIsBho	25.02102294	2024-12-09 12:32:04	2024-12-09 12:32:04
383	PPR-LsgzISHwy5	ITC-ncZ6SSLhOc	38.14385240	2024-12-09 14:02:24	2024-12-09 14:02:24
384	PPR-TIrCNRis2j	ITC-flY9k7HKFl	17.68693230	2024-12-09 14:03:57	2024-12-09 14:03:57
385	PPR-WgrM1tRcNA	ITC-p9IWpWM5vc	36.03431682	2024-12-09 14:06:09	2024-12-09 14:06:09
386	PPR-itV9jajdiv	ITC-wIwSqATAWE	0.38410319	2024-12-09 14:07:03	2024-12-09 14:07:03
387	PPR-xAhULFdTJ1	ITC-nhcMdc7OyW	2.10650323	2024-12-09 14:10:11	2024-12-09 14:10:11
388	PPR-SfqAwCfaGp	ITC-f55SXSrqrR	4.55005768	2024-12-09 14:10:40	2024-12-09 14:10:40
389	PPR-OnADeXZIWG	ITC-vAwQY8gH2H	179.81219606	2024-12-09 14:17:24	2024-12-09 14:17:24
390	PPR-LOibqQtmpI	ITC-0zjnsbw0wu	1.99369357	2024-12-09 14:17:29	2024-12-09 14:17:29
391	PPR-rrHhWJWxfr	ITC-tZg0t4X5xN	2.14550749	2024-12-09 14:18:55	2024-12-09 14:18:55
392	PPR-7T7rz1XIWA	ITC-D7q0DLwZ4y	53.66875201	2024-12-09 14:20:28	2024-12-09 14:20:28
393	PPR-unWwoUTEbs	ITC-oMN46k7kan	1.88692521	2024-12-09 14:32:03	2024-12-09 14:32:03
394	PPR-FMFFtEC4Ug	ITC-MO95F6ARHa	27.22958891	2024-12-09 14:32:26	2024-12-09 14:32:26
395	PPR-mdmJ17r3Ug	ITC-QXGzxMvmKL	118.23509682	2024-12-09 15:48:47	2024-12-09 15:48:47
396	PPR-nw0IJv23ta	ITC-UYLbRC56Pw	2.55931799	2024-12-09 15:48:52	2024-12-09 15:48:52
397	PPR-hWjNqUa5lT	ITC-Z692r80YQL	1.88692521	2024-12-09 16:06:13	2024-12-09 16:06:13
398	PPR-uramf3wCY8	ITC-m7i1VBxXNm	2.10650323	2024-12-09 16:07:38	2024-12-09 16:07:38
399	PPR-Dh6liZXFWH	ITC-GgvO41uKHy	3.75877236	2024-12-09 16:47:04	2024-12-09 16:47:04
400	PPR-VpLuEEodJH	ITC-AySSuDO0gi	27.22958891	2024-12-09 16:47:19	2024-12-09 16:47:19
401	PPR-005mCqcvqX	ITC-YsOvuKKHva	2.53139052	2024-12-09 17:13:03	2024-12-09 17:13:03
402	PPR-oEv46ECAS5	ITC-lWkgBm6M8y	40.28961799	2024-12-09 21:41:11	2024-12-09 21:41:11
403	PPR-eCRpEmPesp	ITC-hKEcezNxWC	2.53828065	2024-12-09 21:41:17	2024-12-09 21:41:17
404	PPR-XJpLQ21W5b	ITC-lvrnrUsdGJ	9.41713234	2024-12-10 04:50:39	2024-12-10 04:50:39
405	PPR-lxRh9o7cWY	ITC-FjvE5ODTlu	67.54177785	2024-12-10 11:54:28	2024-12-10 11:54:28
406	PPR-XLIFSa5EsQ	ITC-YyyMDt1mKa	4.69651746	2024-12-10 12:39:23	2024-12-10 12:39:23
407	PPR-Z5dd25zXTe	ITC-Dcs5Y9nS5L	3.72937805	2024-12-11 04:52:25	2024-12-11 04:52:25
408	PPR-eEV2GguNU6	ITC-IKyyNjzZE0	109.92812092	2024-12-11 17:27:09	2024-12-11 17:27:09
409	PPR-lQ5Pyr10x1	ITC-1h9KUgir50	7.17637807	2024-12-15 17:14:41	2024-12-15 17:14:41
410	PPR-ok2XZE7KfS	ITC-wi4u5b0jdJ	18.01768330	2024-12-16 04:43:57	2024-12-16 04:43:57
411	PPR-aW5srUZNqU	ITC-0zjnsbw0wu	2.03060904	2024-12-16 06:43:16	2024-12-16 06:43:16
412	PPR-o9D3K7W8ah	ITC-IzyqsIsBho	25.58601440	2024-12-16 06:43:25	2024-12-16 06:43:25
413	PPR-bRBUTOMxOD	ITC-vAwQY8gH2H	203.74346552	2024-12-16 06:43:26	2024-12-16 06:43:26
414	PPR-sznxtzY3m4	ITC-oMN46k7kan	1.92186374	2024-12-16 07:31:24	2024-12-16 07:31:24
415	PPR-xKvjX9Je4M	ITC-MO95F6ARHa	27.84445127	2024-12-16 07:31:34	2024-12-16 07:31:34
416	PPR-dTvKMb5vM9	ITC-Z692r80YQL	1.92186374	2024-12-16 07:43:18	2024-12-16 07:43:18
417	PPR-YaOIX8ob6M	ITC-GgvO41uKHy	1.92120988	2024-12-16 08:37:44	2024-12-16 08:37:44
418	PPR-ZjJsO6ac4R	ITC-m7i1VBxXNm	2.14550749	2024-12-16 12:24:27	2024-12-16 12:24:27
419	PPR-MM0B9ozQWG	ITC-YsOvuKKHva	2.57826205	2024-12-16 12:51:30	2024-12-16 12:51:30
420	PPR-Ey306UAIbS	ITC-AySSuDO0gi	27.84445127	2024-12-16 15:46:49	2024-12-16 15:46:49
421	PPR-zqik1ETkwG	ITC-Igk7zIiFps	44.77407830	2024-12-16 16:06:13	2024-12-16 16:06:13
422	PPR-d9epdkBGe6	ITC-lWkgBm6M8y	41.19938456	2024-12-16 17:49:47	2024-12-16 17:49:47
423	PPR-AXubkqi5Cj	ITC-hKEcezNxWC	2.58527976	2024-12-16 17:49:58	2024-12-16 17:49:58
424	PPR-lNbVN6hfhm	ITC-QXGzxMvmKL	120.90492452	2024-12-16 18:05:58	2024-12-16 18:05:58
425	PPR-rkkOjAq00o	ITC-UYLbRC56Pw	2.60670663	2024-12-16 18:06:03	2024-12-16 18:06:03
426	PPR-RqLxRxECyi	ITC-lvrnrUsdGJ	9.59150110	2024-12-16 19:27:34	2024-12-16 19:27:34
427	PPR-4309mj0koR	ITC-FjvE5ODTlu	71.55079015	2024-12-17 11:40:46	2024-12-17 11:40:46
428	PPR-7KsE96qKEW	ITC-YyyMDt1mKa	2.40051668	2024-12-17 16:43:03	2024-12-17 16:43:03
429	PPR-u9xpJ3vJay	ITC-S6dbIk79cE	54.64522140	2024-12-17 19:40:31	2024-12-17 19:40:31
430	PPR-GvFxhIvsJD	ITC-wi4u5b0jdJ	18.42453466	2024-12-18 09:01:26	2024-12-18 09:01:26
431	PPR-ONz5HtR5QC	ITC-Dcs5Y9nS5L	3.81358991	2024-12-18 09:01:31	2024-12-18 09:01:31
432	PPR-gxM42LbGeu	ITC-tZg0t4X5xN	2.18523397	2024-12-18 09:01:35	2024-12-18 09:01:35
433	PPR-mG5SKsocBS	ITC-1h9KUgir50	7.30925675	2024-12-22 14:23:45	2024-12-22 14:23:45
434	PPR-QO17nV83ge	ITC-flY9k7HKFl	18.08631508	2024-12-22 19:10:50	2024-12-22 19:10:50
435	PPR-p4NM8TTR0j	ITC-ncZ6SSLhOc	39.00516614	2024-12-22 19:12:20	2024-12-22 19:12:20
436	PPR-gxnkWUC0Mu	ITC-p9IWpWM5vc	36.84799584	2024-12-22 19:13:50	2024-12-22 19:13:50
437	PPR-iOINOkwpSN	ITC-wIwSqATAWE	0.39121529	2024-12-22 19:15:46	2024-12-22 19:15:46
438	PPR-TYR3pBdYEZ	ITC-nhcMdc7OyW	2.14550749	2024-12-22 19:52:22	2024-12-22 19:52:22
439	PPR-d0DJhWbZ4h	ITC-f55SXSrqrR	4.63430710	2024-12-22 19:52:36	2024-12-22 19:52:36
440	PPR-F0I09cVVhT	ITC-GQFm3SnCSL	39.06242478	2024-12-22 21:49:11	2024-12-22 21:49:11
441	PPR-vZnHaw4rRf	ITC-e8x2GsZeYs	31.20103195	2024-12-22 21:49:16	2024-12-22 21:49:16
442	PPR-O2uY3eZgzw	ITC-lvrnrUsdGJ	9.76909848	2024-12-23 08:01:01	2024-12-23 08:01:01
443	PPR-mmmCt412Fj	ITC-MO95F6ARHa	28.47319764	2024-12-23 08:12:00	2024-12-23 08:12:00
444	PPR-t6nJDnYgwD	ITC-oMN46k7kan	1.95744920	2024-12-23 08:12:12	2024-12-23 08:12:12
445	PPR-i5SkyDtnbH	ITC-AySSuDO0gi	28.47319764	2024-12-23 08:47:20	2024-12-23 08:47:20
446	PPR-xNzsj5bcpf	ITC-IzyqsIsBho	26.16376375	2024-12-23 09:10:30	2024-12-23 09:10:30
447	PPR-tDsLVoqwks	ITC-0zjnsbw0wu	2.06820804	2024-12-23 09:29:32	2024-12-23 09:29:32
448	PPR-7935gfbxOF	ITC-vAwQY8gH2H	208.34412948	2024-12-23 09:46:22	2024-12-23 09:46:22
449	PPR-JmQUQN0nOX	ITC-FjvE5ODTlu	95.06970483	2024-12-23 12:02:47	2024-12-23 12:02:47
450	PPR-Gjlj0YqUET	ITC-QXGzxMvmKL	123.63503872	2024-12-23 17:19:41	2024-12-23 17:19:41
451	PPR-1mQrh7eJh8	ITC-UYLbRC56Pw	2.65497272	2024-12-23 17:19:47	2024-12-23 17:19:47
452	PPR-90Bah1PumA	ITC-gKLELcDX6G	74.63450484	2024-12-23 17:26:17	2024-12-23 17:26:17
453	PPR-KyTYJPHmxW	ITC-S6dbIk79cE	55.87914711	2024-12-23 18:05:45	2024-12-23 18:05:45
454	PPR-shY8crVOX4	ITC-hKEcezNxWC	0.78153711	2024-12-23 19:23:13	2024-12-23 19:23:13
455	PPR-2yrSQt1haF	ITC-lWkgBm6M8y	41.18130613	2024-12-23 19:23:50	2024-12-23 19:23:50
456	PPR-lO6PTwfPn6	ITC-YsOvuKKHva	2.62600146	2024-12-23 21:06:09	2024-12-23 21:06:09
457	PPR-CEa4fsXJhv	ITC-YyyMDt1mKa	2.44496493	2024-12-24 12:32:46	2024-12-24 12:32:46
458	PPR-2crTfT2zWd	ITC-m7i1VBxXNm	0.33362197	2024-12-24 17:04:57	2024-12-24 17:04:57
459	PPR-m032lPOicu	ITC-flY9k7HKFl	18.49471619	2024-12-24 17:50:11	2024-12-24 17:50:11
460	PPR-OVkfJdYqd3	ITC-ncZ6SSLhOc	39.88592893	2024-12-24 17:58:09	2024-12-24 17:58:09
461	PPR-PGzzjl0oZc	ITC-p9IWpWM5vc	37.68004827	2024-12-24 18:01:46	2024-12-24 18:01:46
462	PPR-2cOcj7ztur	ITC-wIwSqATAWE	0.39845908	2024-12-24 18:06:36	2024-12-24 18:06:36
463	PPR-FOwWiD9apE	ITC-nhcMdc7OyW	2.18523397	2024-12-24 18:12:34	2024-12-24 18:12:34
464	PPR-rPyZtA2ecl	ITC-f55SXSrqrR	4.72011648	2024-12-24 18:13:28	2024-12-24 18:13:28
465	PPR-evlFnR7zfN	ITC-D7q0DLwZ4y	54.66248906	2024-12-24 18:21:20	2024-12-24 18:21:20
466	PPR-quZUHu5u2E	ITC-GgvO41uKHy	1.95678323	2024-12-24 20:27:32	2024-12-24 20:27:32
467	PPR-PFRyu7rEO9	ITC-Z692r80YQL	1.95744920	2024-12-24 21:22:12	2024-12-24 21:22:12
468	PPR-A5xQv3Vb2Y	ITC-wi4u5b0jdJ	18.84057300	2024-12-26 11:56:04	2024-12-26 11:56:04
469	PPR-pZTiWjPa7D	ITC-Dcs5Y9nS5L	3.89970332	2024-12-26 11:56:10	2024-12-26 11:56:10
470	PPR-M6PUxeDZ59	ITC-tZg0t4X5xN	2.22569602	2024-12-26 11:56:22	2024-12-26 11:56:22
471	PPR-zUa64aimS6	ITC-WHqNMXYAxy	26.19309776	2024-12-27 14:52:50	2024-12-27 14:52:50
472	PPR-l8VZJnAQKG	ITC-7ApObtH2pi	13.31833512	2024-12-27 14:52:53	2024-12-27 14:52:53
473	PPR-TWE7pFJvwf	ITC-mFhh9aMM9V	1.85161200	2024-12-27 14:52:55	2024-12-27 14:52:55
474	PPR-EjXWhEfvqY	ITC-XbiBgsSJkM	28.49876027	2024-12-27 14:52:58	2024-12-27 14:52:58
475	PPR-MTWaVjaeDf	ITC-FIuyoYe0ta	11.94314867	2024-12-29 12:26:20	2024-12-29 12:26:20
476	PPR-1fL7opbJgW	ITC-G5BiAoEKzU	52.99845579	2024-12-29 12:26:25	2024-12-29 12:26:25
477	PPR-arnGztOXJx	ITC-lykjDQVfnF	25.96777050	2024-12-29 12:26:31	2024-12-29 12:26:31
478	PPR-iE2lXAvJs0	ITC-XKDP9jfHOn	331.51917847	2024-12-29 20:43:53	2024-12-29 20:43:53
479	PPR-XdWrWlfnIg	ITC-hKEcezNxWC	0.79600814	2024-12-29 22:12:20	2024-12-29 22:12:20
480	PPR-bZfgLv0CF2	ITC-lWkgBm6M8y	42.11120761	2024-12-29 22:12:57	2024-12-29 22:12:57
481	PPR-397XmuHaug	ITC-S6dbIk79cE	57.14093569	2024-12-30 04:48:54	2024-12-30 04:48:54
482	PPR-RJZcIRFcfF	ITC-ft4j6MrmEp	28.97156812	2024-12-30 10:04:02	2024-12-30 10:04:02
483	PPR-q3RWrqDxnQ	ITC-lcMghoXuxS	290.09823374	2024-12-30 10:04:06	2024-12-30 10:04:06
484	PPR-684X7LkRvi	ITC-NHnbmFh8Ej	593.87575624	2024-12-30 10:04:09	2024-12-30 10:04:09
485	PPR-kxNBOcd5gL	ITC-QuCgap9eFI	25.85033766	2024-12-30 10:04:13	2024-12-30 10:04:13
486	PPR-ODmaxqEu1t	ITC-vAwQY8gH2H	213.04867951	2024-12-30 13:50:20	2024-12-30 13:50:20
487	PPR-YZueSeYRqv	ITC-WHqNMXYAxy	26.78455546	2024-12-30 14:53:49	2024-12-30 14:53:49
488	PPR-cptxBeZtiD	ITC-7ApObtH2pi	13.61907205	2024-12-30 14:53:52	2024-12-30 14:53:52
489	PPR-xUbKIr9a6D	ITC-XbiBgsSJkM	29.14228137	2024-12-30 14:53:56	2024-12-30 14:53:56
490	PPR-VK6YVKUHhl	ITC-QXGzxMvmKL	126.42680073	2024-12-30 15:25:33	2024-12-30 15:25:33
491	PPR-LK3FZPGJPs	ITC-UYLbRC56Pw	2.70413251	2024-12-30 15:25:38	2024-12-30 15:25:38
492	PPR-hw7jmxj4rz	ITC-m7i1VBxXNm	2.19141135	2024-12-30 17:45:52	2024-12-30 17:45:52
493	PPR-BV9ZyAhrcp	ITC-lvrnrUsdGJ	9.94998428	2024-12-30 19:30:00	2024-12-30 19:30:00
494	PPR-mg2aWz7oCg	ITC-AySSuDO0gi	29.11614152	2024-12-30 21:11:03	2024-12-30 21:11:03
495	PPR-Rk0rlUrUUi	ITC-Z692r80YQL	1.99369357	2024-12-30 23:25:39	2024-12-30 23:25:39
496	PPR-0Ormytm62R	ITC-MO95F6ARHa	29.11614152	2024-12-31 05:37:40	2024-12-31 05:37:40
497	PPR-D0J5OFO2rK	ITC-oMN46k7kan	3.84530557	2024-12-31 05:38:00	2024-12-31 05:38:00
498	PPR-fqVnwNsCrp	ITC-lykjDQVfnF	26.55414016	2024-12-31 07:39:15	2024-12-31 07:39:15
499	PPR-QHrQHpO1ql	ITC-G5BiAoEKzU	54.19519643	2024-12-31 07:39:18	2024-12-31 07:39:18
500	PPR-FzhUVX9xIR	ITC-FIuyoYe0ta	12.21283297	2024-12-31 07:39:23	2024-12-31 07:39:23
501	PPR-yeWO4SfB7L	ITC-gKLELcDX6G	58.13697212	2024-12-31 13:27:40	2024-12-31 13:27:40
502	PPR-rXuQOpBKlS	ITC-FjvE5ODTlu	140.79713556	2024-12-31 14:11:28	2024-12-31 14:11:28
503	PPR-Zgy3LwCgP2	ITC-YsOvuKKHva	2.67462482	2024-12-31 17:30:06	2024-12-31 17:30:06
504	PPR-LykI8QkdfO	ITC-D7q0DLwZ4y	55.67462627	2025-01-01 12:25:04	2025-01-01 12:25:04
505	PPR-UPvFF1ptx2	ITC-f55SXSrqrR	4.80751473	2025-01-01 12:26:35	2025-01-01 12:26:35
506	PPR-imBbrS6WWp	ITC-XKDP9jfHOn	339.75369383	2025-01-01 12:31:32	2025-01-01 12:31:32
507	PPR-KvZJh0EEON	ITC-nhcMdc7OyW	2.22569602	2025-01-01 12:33:19	2025-01-01 12:33:19
508	PPR-y5GbezARMp	ITC-p9IWpWM5vc	38.53088901	2025-01-02 18:42:49	2025-01-02 18:42:49
509	PPR-5YiGr1Agdt	ITC-wIwSqATAWE	0.40583700	2025-01-02 18:45:04	2025-01-02 18:45:04
510	PPR-y98fel4NGv	ITC-ncZ6SSLhOc	40.78657993	2025-01-02 18:46:19	2025-01-02 18:46:19
511	PPR-cCsMaPnscT	ITC-flY9k7HKFl	18.91233928	2025-01-02 18:48:16	2025-01-02 18:48:16
512	PPR-7qJ3JQnhz8	ITC-0zjnsbw0wu	2.10650323	2025-01-04 10:54:53	2025-01-04 10:54:53
513	PPR-YlXaqpclUG	ITC-wi4u5b0jdJ	19.26600576	2025-01-04 14:09:28	2025-01-04 14:09:28
514	PPR-FOxujN30qx	ITC-Dcs5Y9nS5L	3.98776124	2025-01-04 14:09:33	2025-01-04 14:09:33
515	PPR-96VXgcqtCt	ITC-tZg0t4X5xN	2.26690728	2025-01-04 14:09:47	2025-01-04 14:09:47
516	PPR-CQfHQbohVc	ITC-GgvO41uKHy	1.99301527	2025-01-04 14:48:33	2025-01-04 14:48:33
517	PPR-GAdq4DLK6I	ITC-GQFm3SnCSL	40.12089110	2025-01-05 10:39:32	2025-01-05 10:39:32
518	PPR-iNdYnbuKhT	ITC-e8x2GsZeYs	31.90557216	2025-01-05 10:39:36	2025-01-05 10:39:36
519	PPR-H1AMm1dNLt	ITC-1h9KUgir50	14.88919164	2025-01-05 16:30:07	2025-01-05 16:30:07
520	PPR-nDfEu1XxxN	ITC-QXGzxMvmKL	129.28160260	2025-01-06 05:46:38	2025-01-06 05:46:38
521	PPR-YxTMHkkrzX	ITC-UYLbRC56Pw	2.75420256	2025-01-06 05:46:42	2025-01-06 05:46:42
522	PPR-PTmFX3JMeh	ITC-S6dbIk79cE	58.43121630	2025-01-06 07:03:25	2025-01-06 07:03:25
523	PPR-esBr9VImwV	ITC-lvrnrUsdGJ	10.13421938	2025-01-06 07:26:18	2025-01-06 07:26:18
524	PPR-gUS6NVcBjB	ITC-FjvE5ODTlu	143.97642921	2025-01-06 07:36:17	2025-01-06 07:36:17
525	PPR-8eTuQloLOv	ITC-MO95F6ARHa	29.77360350	2025-01-06 09:21:57	2025-01-06 09:21:57
526	PPR-FanCuWKZL4	ITC-oMN46k7kan	3.91650571	2025-01-06 09:22:04	2025-01-06 09:22:04
527	PPR-Yriu82ehFv	ITC-AySSuDO0gi	29.77360350	2025-01-06 10:21:55	2025-01-06 10:21:55
528	PPR-TTA5EB7iwb	ITC-vAwQY8gH2H	217.85946144	2025-01-06 11:06:21	2025-01-06 11:06:21
529	PPR-kYq6cFbV6K	ITC-0zjnsbw0wu	2.14550749	2025-01-06 17:14:09	2025-01-06 17:14:09
530	PPR-RBX6OmCRo7	ITC-YyyMDt1mKa	4.98047240	2025-01-07 06:14:27	2025-01-07 06:14:27
531	PPR-DdL6LbkXru	ITC-wi4u5b0jdJ	19.70104508	2025-01-07 06:29:22	2025-01-07 06:29:22
532	PPR-yMzJUWmkHH	ITC-Dcs5Y9nS5L	4.07780756	2025-01-07 06:29:30	2025-01-07 06:29:30
533	PPR-schxhAJ8BT	ITC-tZg0t4X5xN	2.30888160	2025-01-07 06:29:39	2025-01-07 06:29:39
534	PPR-eIUxZgThin	ITC-D7q0DLwZ4y	56.70550433	2025-01-07 10:51:27	2025-01-07 10:51:27
535	PPR-LgqWDQdSXM	ITC-f55SXSrqrR	4.89653125	2025-01-07 10:52:11	2025-01-07 10:52:11
536	PPR-Pvf3cCG9rw	ITC-nhcMdc7OyW	2.26690728	2025-01-07 10:52:40	2025-01-07 10:52:40
\.


--
-- Data for Name: package_profit_withdraws; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_profit_withdraws (id, uuid, package_uuid, created_at, updated_at) FROM stdin;
1	WPP-n73tRFnBz7	ITC-GQFm3SnCSL	2024-08-25 16:53:30	2024-08-25 16:53:30
2	WPP-AM8lbnMFAd	ITC-e8x2GsZeYs	2024-08-25 16:53:39	2024-08-25 16:53:39
3	WPP-yVb1LoBfSZ	ITC-1e2g490fDv	2024-08-25 18:04:21	2024-08-25 18:04:21
4	WPP-tUyflC07qQ	ITC-1DHfDk80aX	2024-08-25 18:11:42	2024-08-25 18:11:42
5	WPP-dHF5FA87RS	ITC-FIuyoYe0ta	2024-08-29 17:01:54	2024-08-29 17:01:54
6	WPP-aCCY0GdWJJ	ITC-ft4j6MrmEp	2024-08-30 23:01:37	2024-08-30 23:01:37
7	WPP-M57MSehnt2	ITC-FIuyoYe0ta	2024-09-01 05:34:58	2024-09-01 05:34:58
8	WPP-RwmxnFfj1U	ITC-4lqsfdy1G3	2024-09-01 06:11:32	2024-09-01 06:11:32
9	WPP-6gI7j1K5sl	ITC-WHqNMXYAxy	2024-09-01 09:25:17	2024-09-01 09:25:17
10	WPP-rNlpKEhStb	ITC-7ApObtH2pi	2024-09-01 09:25:19	2024-09-01 09:25:19
11	WPP-HbYFGpbuOO	ITC-1e2g490fDv	2024-09-01 17:33:29	2024-09-01 17:33:29
12	WPP-YaEm0QAbIU	ITC-xWYNRrs8yJ	2024-09-01 17:33:38	2024-09-01 17:33:38
13	WPP-phCVlcw6KS	ITC-1DHfDk80aX	2024-09-01 17:35:17	2024-09-01 17:35:17
14	WPP-8d5VYcSK5i	ITC-DlaAptpq2c	2024-09-01 17:35:33	2024-09-01 17:35:33
15	WPP-AS2d2vzedO	ITC-lcMghoXuxS	2024-09-01 19:12:08	2024-09-01 19:12:08
16	WPP-hvZ8m2YaUq	ITC-NHnbmFh8Ej	2024-09-01 19:12:28	2024-09-01 19:12:28
17	WPP-Tu36fYHgZs	ITC-ft4j6MrmEp	2024-09-02 17:36:56	2024-09-02 17:36:56
18	WPP-8pmAf6xmQi	ITC-lcMghoXuxS	2024-09-02 17:36:59	2024-09-02 17:36:59
19	WPP-p9puLjuVRS	ITC-NHnbmFh8Ej	2024-09-02 17:37:00	2024-09-02 17:37:00
20	WPP-crCPDvJbUj	ITC-ncLOsPXpV0	2024-09-03 17:38:24	2024-09-03 17:38:24
21	WPP-vk7loKsesE	ITC-0KZCWKi2eL	2024-09-06 05:55:16	2024-09-06 05:55:16
22	WPP-rJiIPTbuRF	ITC-ft4j6MrmEp	2024-09-08 06:14:36	2024-09-08 06:14:36
23	WPP-CcSxPVeVfw	ITC-lcMghoXuxS	2024-09-08 06:14:40	2024-09-08 06:14:40
24	WPP-gYuojIz0Kj	ITC-NHnbmFh8Ej	2024-09-08 06:14:44	2024-09-08 06:14:44
25	WPP-7NMsrs2cOz	ITC-QuCgap9eFI	2024-09-08 06:14:48	2024-09-08 06:14:48
26	WPP-J9Pj6ehWVt	ITC-1e2g490fDv	2024-09-08 13:16:57	2024-09-08 13:16:57
27	WPP-sozvUm8gN3	ITC-xWYNRrs8yJ	2024-09-08 13:17:19	2024-09-08 13:17:19
28	WPP-uXpJT2xttj	ITC-1DHfDk80aX	2024-09-08 13:18:37	2024-09-08 13:18:37
29	WPP-bVo9gT19f7	ITC-DlaAptpq2c	2024-09-08 13:18:39	2024-09-08 13:18:39
30	WPP-mz6ehE2t0h	ITC-GQFm3SnCSL	2024-09-08 14:57:24	2024-09-08 14:57:24
31	WPP-zJraM77382	ITC-e8x2GsZeYs	2024-09-08 14:57:26	2024-09-08 14:57:26
32	WPP-4HOLYmxnNv	ITC-ncLOsPXpV0	2024-09-08 16:31:48	2024-09-08 16:31:48
33	WPP-xSxSKU4YA8	ITC-NHnbmFh8Ej	2024-09-12 13:11:42	2024-09-12 13:11:42
34	WPP-SvTlINFVJD	ITC-QuCgap9eFI	2024-09-12 13:11:47	2024-09-12 13:11:47
35	WPP-KNhgWfcuQc	ITC-ft4j6MrmEp	2024-09-12 13:11:51	2024-09-12 13:11:51
36	WPP-WF49mq3DDT	ITC-lcMghoXuxS	2024-09-12 13:11:56	2024-09-12 13:11:56
37	WPP-IlHjAFhal3	ITC-3uK8WuMYbf	2024-09-15 11:08:30	2024-09-15 11:08:30
38	WPP-m5sOCjd6hh	ITC-zr840ljEPc	2024-09-15 11:30:46	2024-09-15 11:30:46
39	WPP-soP2ig3CuB	ITC-BKrKKpzteK	2024-09-15 11:47:10	2024-09-15 11:47:10
40	WPP-GM9Won2OHU	ITC-gngFgoaoGi	2024-09-15 11:52:00	2024-09-15 11:52:00
41	WPP-IDK3gVfQFV	ITC-xWYNRrs8yJ	2024-09-15 15:45:58	2024-09-15 15:45:58
42	WPP-N9MPVz1x7Q	ITC-1e2g490fDv	2024-09-15 15:46:02	2024-09-15 15:46:02
43	WPP-JL9tECGhwc	ITC-1DHfDk80aX	2024-09-15 15:46:59	2024-09-15 15:46:59
44	WPP-n9DiAF18l5	ITC-DlaAptpq2c	2024-09-15 15:47:03	2024-09-15 15:47:03
45	WPP-rTn6H5sRIg	ITC-7rUVFjBAdo	2024-09-16 16:53:53	2024-09-16 16:53:53
46	WPP-VNapwJXJdr	ITC-4lqsfdy1G3	2024-09-17 06:09:55	2024-09-17 06:09:55
47	WPP-OEoGt4tmim	ITC-WHqNMXYAxy	2024-09-22 02:35:59	2024-09-22 02:35:59
48	WPP-l54i1l7m6b	ITC-7ApObtH2pi	2024-09-22 02:36:02	2024-09-22 02:36:02
49	WPP-OKvvSAWr3O	ITC-XbiBgsSJkM	2024-09-22 02:36:14	2024-09-22 02:36:14
50	WPP-qaLdg4pFQn	ITC-mFhh9aMM9V	2024-09-22 02:36:16	2024-09-22 02:36:16
51	WPP-xFvUfYqSwf	ITC-G5BiAoEKzU	2024-09-22 04:25:47	2024-09-22 04:25:47
52	WPP-DiT8YmMcMI	ITC-wKyyQSfDUp	2024-09-22 04:26:41	2024-09-22 04:26:41
53	WPP-Xm07ORMS4a	ITC-FIuyoYe0ta	2024-09-22 04:27:15	2024-09-22 04:27:15
54	WPP-LUxXzbOzYQ	ITC-lcMghoXuxS	2024-09-22 05:37:20	2024-09-22 05:37:20
55	WPP-X0BKvVWnIM	ITC-ft4j6MrmEp	2024-09-22 05:37:23	2024-09-22 05:37:23
56	WPP-myJVDzQu7I	ITC-QuCgap9eFI	2024-09-22 05:37:27	2024-09-22 05:37:27
57	WPP-T5G2dMqmSw	ITC-NHnbmFh8Ej	2024-09-22 05:37:33	2024-09-22 05:37:33
58	WPP-0inudkOxBs	ITC-e8x2GsZeYs	2024-09-22 06:31:03	2024-09-22 06:31:03
59	WPP-QwrU6QC7zI	ITC-GQFm3SnCSL	2024-09-22 06:31:05	2024-09-22 06:31:05
60	WPP-6aIqNaMk07	ITC-xWYNRrs8yJ	2024-09-22 14:55:59	2024-09-22 14:55:59
61	WPP-rBi91Xglk1	ITC-1e2g490fDv	2024-09-22 14:56:03	2024-09-22 14:56:03
62	WPP-5xn0uGn4z2	ITC-1DHfDk80aX	2024-09-22 14:56:30	2024-09-22 14:56:30
63	WPP-sbeAYnCeBD	ITC-DlaAptpq2c	2024-09-22 14:56:32	2024-09-22 14:56:32
64	WPP-mjyDu5dFwq	ITC-ft4j6MrmEp	2024-09-27 05:15:45	2024-09-27 05:15:45
65	WPP-82GZiE7Ejd	ITC-lcMghoXuxS	2024-09-27 05:15:49	2024-09-27 05:15:49
66	WPP-ZYTptTW2QJ	ITC-NHnbmFh8Ej	2024-09-27 05:15:52	2024-09-27 05:15:52
67	WPP-qIBjTfk6QZ	ITC-QuCgap9eFI	2024-09-27 05:15:57	2024-09-27 05:15:57
68	WPP-F3OjCsee8I	ITC-4lqsfdy1G3	2024-09-29 06:12:37	2024-09-29 06:12:37
69	WPP-4w6XwYpsPK	ITC-FIuyoYe0ta	2024-09-29 07:39:36	2024-09-29 07:39:36
70	WPP-mY1FVu5Q4i	ITC-wKyyQSfDUp	2024-09-29 07:39:42	2024-09-29 07:39:42
71	WPP-F1CeFeq0e5	ITC-XKDP9jfHOn	2024-09-29 16:40:22	2024-09-29 16:40:22
72	WPP-OwBocWUbUm	ITC-xWYNRrs8yJ	2024-09-29 18:28:30	2024-09-29 18:28:30
73	WPP-uhiKeWXaj7	ITC-1e2g490fDv	2024-09-29 18:28:38	2024-09-29 18:28:38
74	WPP-vTE2pnXo71	ITC-1DHfDk80aX	2024-09-29 18:31:33	2024-09-29 18:31:33
75	WPP-f5gP2B1HpY	ITC-DlaAptpq2c	2024-09-29 18:31:37	2024-09-29 18:31:37
76	WPP-9HBFyEh8oZ	ITC-ncLOsPXpV0	2024-09-30 16:27:37	2024-09-30 16:27:37
77	WPP-eIBaKLxroF	ITC-2KcILuMvKT	2024-09-30 19:17:51	2024-09-30 19:17:51
78	WPP-tqDO9eyuwr	ITC-7rUVFjBAdo	2024-09-30 19:17:59	2024-09-30 19:17:59
79	WPP-K9brdaEAWY	ITC-stchngBjHP	2024-09-30 19:18:04	2024-09-30 19:18:04
80	WPP-MASyDWGeiF	ITC-YsOvuKKHva	2024-09-30 20:35:11	2024-09-30 20:35:11
81	WPP-bMQBMZxcC1	ITC-4lqsfdy1G3	2024-10-01 18:52:22	2024-10-01 18:52:22
82	WPP-XE5DfnAN1f	ITC-ft4j6MrmEp	2024-10-04 09:50:16	2024-10-04 09:50:16
83	WPP-WCgOx9hFoH	ITC-lcMghoXuxS	2024-10-04 10:07:01	2024-10-04 10:07:01
84	WPP-pTjppQpVjV	ITC-NHnbmFh8Ej	2024-10-04 10:07:05	2024-10-04 10:07:05
85	WPP-hajBZdBN70	ITC-QuCgap9eFI	2024-10-04 10:07:10	2024-10-04 10:07:10
86	WPP-qhdsX2MFXs	ITC-G5BiAoEKzU	2024-10-06 09:18:49	2024-10-06 09:18:49
87	WPP-40WQPecpYA	ITC-FIuyoYe0ta	2024-10-06 09:18:54	2024-10-06 09:18:54
88	WPP-ewvzk0JpEo	ITC-wKyyQSfDUp	2024-10-06 09:18:57	2024-10-06 09:18:57
89	WPP-HTwd2PSJ7b	ITC-mFhh9aMM9V	2024-10-06 09:54:27	2024-10-06 09:54:27
90	WPP-TJxTxtryAF	ITC-GQFm3SnCSL	2024-10-06 16:10:36	2024-10-06 16:10:36
91	WPP-4oJkhUimjH	ITC-e8x2GsZeYs	2024-10-06 16:10:40	2024-10-06 16:10:40
92	WPP-LmdVU3E2DC	ITC-xWYNRrs8yJ	2024-10-06 16:24:08	2024-10-06 16:24:08
93	WPP-32vxHXCZJF	ITC-1e2g490fDv	2024-10-06 16:24:11	2024-10-06 16:24:11
94	WPP-42103eXKmS	ITC-1DHfDk80aX	2024-10-06 16:24:44	2024-10-06 16:24:44
95	WPP-dDNnYP3m51	ITC-DlaAptpq2c	2024-10-06 16:24:47	2024-10-06 16:24:47
96	WPP-zoiPDNVL6I	ITC-ft4j6MrmEp	2024-10-09 19:10:51	2024-10-09 19:10:51
97	WPP-WQSpeyzjmg	ITC-lcMghoXuxS	2024-10-09 19:10:55	2024-10-09 19:10:55
98	WPP-pKHy5tYn1Q	ITC-NHnbmFh8Ej	2024-10-09 19:10:58	2024-10-09 19:10:58
99	WPP-4shYUZZ9GS	ITC-QuCgap9eFI	2024-10-09 19:11:05	2024-10-09 19:11:05
100	WPP-WxbY7GScxi	ITC-4lqsfdy1G3	2024-10-11 12:33:08	2024-10-11 12:33:08
101	WPP-jDtDB80gaF	ITC-D7q0DLwZ4y	2024-10-13 07:08:58	2024-10-13 07:08:58
102	WPP-KVz1yUWTXa	ITC-ncLOsPXpV0	2024-10-13 12:49:07	2024-10-13 12:49:07
103	WPP-5aPg9dZPIT	ITC-G5BiAoEKzU	2024-10-13 13:55:45	2024-10-13 13:55:45
104	WPP-BRcguVtYni	ITC-FIuyoYe0ta	2024-10-13 13:55:47	2024-10-13 13:55:47
105	WPP-W29s3cdeWv	ITC-wKyyQSfDUp	2024-10-13 13:55:52	2024-10-13 13:55:52
106	WPP-4YNPl7KgrY	ITC-xWYNRrs8yJ	2024-10-13 17:43:53	2024-10-13 17:43:53
107	WPP-0RnVAxJ8bj	ITC-1e2g490fDv	2024-10-13 17:43:59	2024-10-13 17:43:59
108	WPP-nsQ4LV3uf9	ITC-1DHfDk80aX	2024-10-13 17:44:39	2024-10-13 17:44:39
109	WPP-87f9BEeUdL	ITC-DlaAptpq2c	2024-10-13 17:44:47	2024-10-13 17:44:47
110	WPP-XXklc9yWp1	ITC-mFhh9aMM9V	2024-10-14 10:28:56	2024-10-14 10:28:56
111	WPP-lvgBcYyrDd	ITC-WHqNMXYAxy	2024-10-14 10:29:45	2024-10-14 10:29:45
112	WPP-2415DIyMbD	ITC-7ApObtH2pi	2024-10-14 10:29:48	2024-10-14 10:29:48
113	WPP-OjuJ0mkD6t	ITC-XbiBgsSJkM	2024-10-14 10:29:50	2024-10-14 10:29:50
114	WPP-xtKrBQoqQw	ITC-7rUVFjBAdo	2024-10-14 12:01:53	2024-10-14 12:01:53
115	WPP-JKaYbI30fy	ITC-2KcILuMvKT	2024-10-14 12:01:56	2024-10-14 12:01:56
116	WPP-4c7T8iNBKK	ITC-stchngBjHP	2024-10-14 12:02:00	2024-10-14 12:02:00
117	WPP-Ytxez7ZlQK	ITC-QuCgap9eFI	2024-10-16 02:53:45	2024-10-16 02:53:45
118	WPP-5iUZs0G2SC	ITC-NHnbmFh8Ej	2024-10-16 02:53:49	2024-10-16 02:53:49
119	WPP-45gJlwuycb	ITC-lcMghoXuxS	2024-10-16 02:53:57	2024-10-16 02:53:57
120	WPP-8gPM6QD4E3	ITC-ft4j6MrmEp	2024-10-16 02:56:33	2024-10-16 02:56:33
121	WPP-sLoMavUwDI	ITC-FIuyoYe0ta	2024-10-19 05:15:00	2024-10-19 05:15:00
122	WPP-U7kj9a7ues	ITC-wKyyQSfDUp	2024-10-19 05:15:03	2024-10-19 05:15:03
123	WPP-hGYTw2aJvj	ITC-4lqsfdy1G3	2024-10-20 09:29:47	2024-10-20 09:29:47
124	WPP-mrFbTEEdqI	ITC-GQFm3SnCSL	2024-10-20 10:47:51	2024-10-20 10:47:51
125	WPP-QJmL2t6OxD	ITC-e8x2GsZeYs	2024-10-20 10:47:54	2024-10-20 10:47:54
126	WPP-iE3BLuJeyY	ITC-WHqNMXYAxy	2024-10-20 10:53:50	2024-10-20 10:53:50
127	WPP-8qfzbb26Ye	ITC-7ApObtH2pi	2024-10-20 10:53:52	2024-10-20 10:53:52
128	WPP-DoqwVXmdlM	ITC-XbiBgsSJkM	2024-10-20 10:53:55	2024-10-20 10:53:55
129	WPP-b58pLxgfXx	ITC-mFhh9aMM9V	2024-10-20 10:53:56	2024-10-20 10:53:56
130	WPP-Oz73oVrTYw	ITC-wi4u5b0jdJ	2024-10-20 13:40:23	2024-10-20 13:40:23
131	WPP-pkY6YVZRnI	ITC-gngFgoaoGi	2024-10-20 13:54:03	2024-10-20 13:54:03
132	WPP-c8iAgc12pQ	ITC-3uK8WuMYbf	2024-10-20 14:01:27	2024-10-20 14:01:27
133	WPP-oSuXk8ugXC	ITC-BKrKKpzteK	2024-10-20 14:09:49	2024-10-20 14:09:49
134	WPP-GDJaWKK94d	ITC-zr840ljEPc	2024-10-20 14:16:30	2024-10-20 14:16:30
135	WPP-EYdHLAbkY4	ITC-xWYNRrs8yJ	2024-10-20 18:16:28	2024-10-20 18:16:28
136	WPP-vjwcMWVQtw	ITC-1e2g490fDv	2024-10-20 18:16:33	2024-10-20 18:16:33
137	WPP-nzOP2rlJV2	ITC-1DHfDk80aX	2024-10-20 18:17:52	2024-10-20 18:17:52
138	WPP-ZSceZcGzSR	ITC-DlaAptpq2c	2024-10-20 18:17:55	2024-10-20 18:17:55
139	WPP-Odg6a1fxn2	ITC-XKDP9jfHOn	2024-10-21 14:53:40	2024-10-21 14:53:40
140	WPP-gKDIkgBlPj	ITC-D7q0DLwZ4y	2024-10-21 14:56:16	2024-10-21 14:56:16
141	WPP-kz8EqsLC30	ITC-ft4j6MrmEp	2024-10-22 05:49:16	2024-10-22 05:49:16
142	WPP-IlVedR6xyD	ITC-lcMghoXuxS	2024-10-22 05:49:20	2024-10-22 05:49:20
143	WPP-QaJijhV3gk	ITC-NHnbmFh8Ej	2024-10-22 05:49:23	2024-10-22 05:49:23
144	WPP-0v64afSAvq	ITC-QuCgap9eFI	2024-10-22 05:49:27	2024-10-22 05:49:27
145	WPP-iKm4HUW94n	ITC-mFhh9aMM9V	2024-10-22 09:18:46	2024-10-22 09:18:46
146	WPP-NoWqUHsrmU	ITC-2KcILuMvKT	2024-10-24 17:25:26	2024-10-24 17:25:26
147	WPP-6Y1FCoY6az	ITC-7rUVFjBAdo	2024-10-24 17:25:29	2024-10-24 17:25:29
148	WPP-gArqCZbuG2	ITC-stchngBjHP	2024-10-24 17:25:32	2024-10-24 17:25:32
149	WPP-DRiBDZ2GJu	ITC-G5BiAoEKzU	2024-10-27 07:59:14	2024-10-27 07:59:14
150	WPP-WmZhZNr4bL	ITC-FIuyoYe0ta	2024-10-27 07:59:16	2024-10-27 07:59:16
151	WPP-GAxuUQlmgY	ITC-wKyyQSfDUp	2024-10-27 07:59:19	2024-10-27 07:59:19
152	WPP-N1mhQcOPXr	ITC-4lqsfdy1G3	2024-10-27 14:27:41	2024-10-27 14:27:41
153	WPP-57ufVLgAjq	ITC-DlaAptpq2c	2024-10-27 15:37:40	2024-10-27 15:37:40
154	WPP-9MqcUrXZfJ	ITC-1DHfDk80aX	2024-10-27 15:39:18	2024-10-27 15:39:18
155	WPP-jdEoI9OvQ4	ITC-1e2g490fDv	2024-10-27 15:53:39	2024-10-27 15:53:39
156	WPP-cvwy8i6LIQ	ITC-IKyyNjzZE0	2024-10-27 15:58:50	2024-10-27 15:58:50
157	WPP-eaHQwsUQRG	ITC-xWYNRrs8yJ	2024-10-27 15:59:43	2024-10-27 15:59:43
158	WPP-G2tz4GMkSY	ITC-2KcILuMvKT	2024-10-28 15:46:09	2024-10-28 15:46:09
159	WPP-SKyu3mYMHv	ITC-7rUVFjBAdo	2024-10-28 15:46:15	2024-10-28 15:46:15
160	WPP-CA2dZ2wEc7	ITC-stchngBjHP	2024-10-28 15:46:18	2024-10-28 15:46:18
161	WPP-rZZrlD3Eus	ITC-pr2IoJIB0z	2024-10-28 15:46:22	2024-10-28 15:46:22
162	WPP-5PAd3N26MA	ITC-mFhh9aMM9V	2024-10-29 04:59:18	2024-10-29 04:59:18
163	WPP-hewrTuwTcH	ITC-ft4j6MrmEp	2024-11-01 00:02:57	2024-11-01 00:02:57
164	WPP-iYvPs6IrH7	ITC-lcMghoXuxS	2024-11-01 00:04:16	2024-11-01 00:04:16
165	WPP-vu1ZJMFbys	ITC-NHnbmFh8Ej	2024-11-01 00:04:20	2024-11-01 00:04:20
166	WPP-rcQVKd6jjO	ITC-QuCgap9eFI	2024-11-01 00:04:23	2024-11-01 00:04:23
167	WPP-UCb0c0VLtc	ITC-G5BiAoEKzU	2024-11-03 07:57:44	2024-11-03 07:57:44
168	WPP-MSXrbK2YfV	ITC-FIuyoYe0ta	2024-11-03 07:57:48	2024-11-03 07:57:48
169	WPP-q7zpecOnhL	ITC-wKyyQSfDUp	2024-11-03 07:57:51	2024-11-03 07:57:51
170	WPP-ovEGnWBRwk	ITC-4lqsfdy1G3	2024-11-03 09:00:11	2024-11-03 09:00:11
171	WPP-sBV1Es059u	ITC-GQFm3SnCSL	2024-11-03 12:33:19	2024-11-03 12:33:19
172	WPP-nbPYMkWneR	ITC-e8x2GsZeYs	2024-11-03 12:33:21	2024-11-03 12:33:21
173	WPP-ZqQbKI0tRp	ITC-GvO24AQJZE	2024-11-03 14:17:54	2024-11-03 14:17:54
174	WPP-CWiS7ph9AB	ITC-xWYNRrs8yJ	2024-11-03 17:14:03	2024-11-03 17:14:03
175	WPP-cIPYKNk85S	ITC-1e2g490fDv	2024-11-03 17:14:06	2024-11-03 17:14:06
176	WPP-YjDfbs37QB	ITC-1DHfDk80aX	2024-11-03 17:14:33	2024-11-03 17:14:33
177	WPP-ctv2jRUv0S	ITC-DlaAptpq2c	2024-11-03 17:14:35	2024-11-03 17:14:35
178	WPP-URoV1LMU8c	ITC-IKyyNjzZE0	2024-11-04 16:42:22	2024-11-04 16:42:22
179	WPP-DXUpE9VOBj	ITC-SNfUA4zPX9	2024-11-05 08:12:39	2024-11-05 08:12:39
180	WPP-5ktJc0vQpH	ITC-NK4e9qdvdM	2024-11-05 08:13:35	2024-11-05 08:13:35
181	WPP-Hdp3rkPdfL	ITC-y9rHXdOAhj	2024-11-05 08:13:58	2024-11-05 08:13:58
182	WPP-IurQu2QxyK	ITC-WHqNMXYAxy	2024-11-06 09:47:52	2024-11-06 09:47:52
183	WPP-trQxIGWAWX	ITC-7ApObtH2pi	2024-11-06 09:47:54	2024-11-06 09:47:54
184	WPP-7fnYPBYKRQ	ITC-XbiBgsSJkM	2024-11-06 09:47:55	2024-11-06 09:47:55
185	WPP-GesbRPkC3F	ITC-mFhh9aMM9V	2024-11-06 09:47:57	2024-11-06 09:47:57
186	WPP-ztRKF1hpQN	ITC-QuCgap9eFI	2024-11-07 23:44:39	2024-11-07 23:44:39
187	WPP-kQqctxZBaS	ITC-NHnbmFh8Ej	2024-11-07 23:44:42	2024-11-07 23:44:42
188	WPP-TdmU84wI1c	ITC-lcMghoXuxS	2024-11-07 23:44:44	2024-11-07 23:44:44
189	WPP-3bTrO5HQLt	ITC-ft4j6MrmEp	2024-11-07 23:44:48	2024-11-07 23:44:48
190	WPP-h9DAvjRGmd	ITC-4lqsfdy1G3	2024-11-10 06:35:28	2024-11-10 06:35:28
191	WPP-sOJ34IDYI1	ITC-2KcILuMvKT	2024-11-10 09:08:05	2024-11-10 09:08:05
192	WPP-PLUVuNk8Yv	ITC-7rUVFjBAdo	2024-11-10 09:08:08	2024-11-10 09:08:08
193	WPP-agK3Hq7l5g	ITC-stchngBjHP	2024-11-10 09:08:11	2024-11-10 09:08:11
194	WPP-YosCQ9EwKi	ITC-pr2IoJIB0z	2024-11-10 09:08:15	2024-11-10 09:08:15
195	WPP-IGHkZNARgS	ITC-1DHfDk80aX	2024-11-10 13:03:32	2024-11-10 13:03:32
196	WPP-NpQE1LxoPS	ITC-DlaAptpq2c	2024-11-10 13:03:35	2024-11-10 13:03:35
197	WPP-kyQhXpWoOw	ITC-xWYNRrs8yJ	2024-11-10 13:04:43	2024-11-10 13:04:43
198	WPP-kb67d2igqS	ITC-1e2g490fDv	2024-11-10 13:04:46	2024-11-10 13:04:46
199	WPP-zlfCbuUQMt	ITC-FIuyoYe0ta	2024-11-10 16:00:04	2024-11-10 16:00:04
200	WPP-HmMyHMPr46	ITC-G5BiAoEKzU	2024-11-10 16:00:08	2024-11-10 16:00:08
201	WPP-OwKwCl6IPt	ITC-wKyyQSfDUp	2024-11-10 16:00:11	2024-11-10 16:00:11
202	WPP-hM1qxFelPb	ITC-SNfUA4zPX9	2024-11-11 10:22:31	2024-11-11 10:22:31
203	WPP-IjaBnhq2Mq	ITC-NK4e9qdvdM	2024-11-11 10:22:41	2024-11-11 10:22:41
204	WPP-GOsJ9CiaqZ	ITC-y9rHXdOAhj	2024-11-11 10:22:51	2024-11-11 10:22:51
205	WPP-1qe6TyXDFA	ITC-IKyyNjzZE0	2024-11-12 10:39:48	2024-11-12 10:39:48
206	WPP-vuw12qmWiN	ITC-mFhh9aMM9V	2024-11-13 03:35:47	2024-11-13 03:35:47
207	WPP-9ZjRrVSWye	ITC-GvO24AQJZE	2024-11-13 07:14:46	2024-11-13 07:14:46
208	WPP-xEV2DSIQRg	ITC-ft4j6MrmEp	2024-11-14 09:25:00	2024-11-14 09:25:00
209	WPP-jCZWtlWweX	ITC-lcMghoXuxS	2024-11-14 09:25:04	2024-11-14 09:25:04
210	WPP-ausVlzAS0F	ITC-NHnbmFh8Ej	2024-11-14 09:25:06	2024-11-14 09:25:06
211	WPP-PuHnL1jaYc	ITC-QuCgap9eFI	2024-11-14 09:25:16	2024-11-14 09:25:16
212	WPP-mWDtWgspc0	ITC-FIuyoYe0ta	2024-11-15 10:31:58	2024-11-15 10:31:58
213	WPP-1UzmmMKmLL	ITC-wKyyQSfDUp	2024-11-15 10:32:01	2024-11-15 10:32:01
214	WPP-jmD5J96aqn	ITC-G5BiAoEKzU	2024-11-15 10:32:04	2024-11-15 10:32:04
215	WPP-uUh7Bknihq	ITC-2KcILuMvKT	2024-11-17 07:23:26	2024-11-17 07:23:26
216	WPP-detCw6qbQJ	ITC-7rUVFjBAdo	2024-11-17 07:23:28	2024-11-17 07:23:28
217	WPP-oa5dSJkuCf	ITC-stchngBjHP	2024-11-17 07:23:31	2024-11-17 07:23:31
218	WPP-eyaDezAbfS	ITC-pr2IoJIB0z	2024-11-17 07:23:34	2024-11-17 07:23:34
219	WPP-dFj0YDsV8b	ITC-xWYNRrs8yJ	2024-11-17 11:28:25	2024-11-17 11:28:25
220	WPP-ZOyWxbxEHb	ITC-1e2g490fDv	2024-11-17 11:28:29	2024-11-17 11:28:29
221	WPP-lYxfA2ibUl	ITC-1DHfDk80aX	2024-11-17 11:35:51	2024-11-17 11:35:51
222	WPP-gMkQEtykrk	ITC-DlaAptpq2c	2024-11-17 11:35:56	2024-11-17 11:35:56
223	WPP-NlypDCzMEO	ITC-GQFm3SnCSL	2024-11-17 13:21:14	2024-11-17 13:21:14
224	WPP-sl5MH7FSl3	ITC-e8x2GsZeYs	2024-11-17 13:21:16	2024-11-17 13:21:16
225	WPP-oHUfag91oQ	ITC-BKrKKpzteK	2024-11-17 19:49:47	2024-11-17 19:49:47
226	WPP-2iyqPVxjOY	ITC-3uK8WuMYbf	2024-11-17 19:55:42	2024-11-17 19:55:42
227	WPP-e9GoSkpZ0k	ITC-gngFgoaoGi	2024-11-17 20:01:48	2024-11-17 20:01:48
228	WPP-zYuruTPppi	ITC-zr840ljEPc	2024-11-17 20:08:13	2024-11-17 20:08:13
229	WPP-FjemJLfi7v	ITC-SNfUA4zPX9	2024-11-18 10:07:14	2024-11-18 10:07:14
230	WPP-bqGCOIJJn4	ITC-NK4e9qdvdM	2024-11-18 10:07:23	2024-11-18 10:07:23
231	WPP-7k1NxyuUOY	ITC-y9rHXdOAhj	2024-11-18 10:07:29	2024-11-18 10:07:29
232	WPP-XeWGKhlOgI	ITC-hKEcezNxWC	2024-11-18 19:08:38	2024-11-18 19:08:38
233	WPP-Kq8Wt7lDo5	ITC-lWkgBm6M8y	2024-11-18 19:08:40	2024-11-18 19:08:40
234	WPP-wuXOEpdwGI	ITC-WHqNMXYAxy	2024-11-22 08:37:08	2024-11-22 08:37:08
235	WPP-26jS5QPYib	ITC-7ApObtH2pi	2024-11-22 08:37:10	2024-11-22 08:37:10
236	WPP-QVLi985Q5q	ITC-mFhh9aMM9V	2024-11-22 08:37:12	2024-11-22 08:37:12
237	WPP-7R9r81DsOf	ITC-XbiBgsSJkM	2024-11-22 08:37:14	2024-11-22 08:37:14
238	WPP-LqnCmwbBY8	ITC-pr2IoJIB0z	2024-11-22 19:34:57	2024-11-22 19:34:57
239	WPP-5dijMpBwdT	ITC-stchngBjHP	2024-11-22 19:35:00	2024-11-22 19:35:00
240	WPP-yFzEDbnVid	ITC-7rUVFjBAdo	2024-11-22 19:35:03	2024-11-22 19:35:03
241	WPP-0L673CbpKo	ITC-2KcILuMvKT	2024-11-22 19:35:05	2024-11-22 19:35:05
242	WPP-Trn19mHFQU	ITC-ft4j6MrmEp	2024-11-24 02:56:24	2024-11-24 02:56:24
243	WPP-jvoqcYjmvS	ITC-lcMghoXuxS	2024-11-24 02:56:28	2024-11-24 02:56:28
244	WPP-Lz8VJtL3nL	ITC-NHnbmFh8Ej	2024-11-24 02:56:31	2024-11-24 02:56:31
245	WPP-VQZpLUsVin	ITC-QuCgap9eFI	2024-11-24 02:56:34	2024-11-24 02:56:34
246	WPP-e9wou7RTbA	ITC-QXGzxMvmKL	2024-11-24 03:04:15	2024-11-24 03:04:15
247	WPP-F7Gaa6UEZx	ITC-xWYNRrs8yJ	2024-11-24 13:06:41	2024-11-24 13:06:41
248	WPP-Rq3m7vnZGK	ITC-1e2g490fDv	2024-11-24 13:06:45	2024-11-24 13:06:45
249	WPP-AbP4gELvY4	ITC-1DHfDk80aX	2024-11-24 13:09:18	2024-11-24 13:09:18
250	WPP-p4hjPxWumJ	ITC-DlaAptpq2c	2024-11-24 13:09:22	2024-11-24 13:09:22
251	WPP-Y6VidELOXC	ITC-lWkgBm6M8y	2024-11-25 10:37:50	2024-11-25 10:37:50
252	WPP-wrteIK6iPL	ITC-hKEcezNxWC	2024-11-25 10:37:54	2024-11-25 10:37:54
253	WPP-MAFlZ7KWkS	ITC-SNfUA4zPX9	2024-11-25 14:39:08	2024-11-25 14:39:08
254	WPP-IA4SALsJcx	ITC-NK4e9qdvdM	2024-11-25 14:39:14	2024-11-25 14:39:14
255	WPP-Tz6LNwKZh7	ITC-y9rHXdOAhj	2024-11-25 14:39:22	2024-11-25 14:39:22
256	WPP-rLqsVvF81F	ITC-mFhh9aMM9V	2024-11-26 08:44:59	2024-11-26 08:44:59
257	WPP-NlCPF9Zxqp	ITC-NHnbmFh8Ej	2024-11-30 01:37:48	2024-11-30 01:37:48
258	WPP-U7d5vojBYc	ITC-QuCgap9eFI	2024-11-30 01:37:52	2024-11-30 01:37:52
259	WPP-nchl4DzMJi	ITC-ft4j6MrmEp	2024-11-30 01:37:55	2024-11-30 01:37:55
260	WPP-l4fjZqNFJD	ITC-lcMghoXuxS	2024-11-30 01:37:58	2024-11-30 01:37:58
261	WPP-aF9u6nvZME	ITC-XKDP9jfHOn	2024-11-30 20:34:47	2024-11-30 20:34:47
262	WPP-gQfUcP2IjL	ITC-GQFm3SnCSL	2024-12-01 10:32:10	2024-12-01 10:32:10
263	WPP-Caqv5blQ7p	ITC-e8x2GsZeYs	2024-12-01 10:32:14	2024-12-01 10:32:14
264	WPP-bkbdUeZakj	ITC-ze20g04VB2	2024-12-02 06:50:51	2024-12-02 06:50:51
265	WPP-5KlVH4sWNx	ITC-0KZCWKi2eL	2024-12-02 06:51:02	2024-12-02 06:51:02
266	WPP-QDrClF8Qmq	ITC-SNfUA4zPX9	2024-12-02 13:56:43	2024-12-02 13:56:43
267	WPP-9AbCZCWArE	ITC-NK4e9qdvdM	2024-12-02 13:56:58	2024-12-02 13:56:58
268	WPP-i5LVVCQjZE	ITC-y9rHXdOAhj	2024-12-02 13:57:07	2024-12-02 13:57:07
269	WPP-IFaFtGooz4	ITC-N6BOPMHJ5z	2024-12-03 16:22:29	2024-12-03 16:22:29
270	WPP-KYA4qTZyCK	ITC-2KcILuMvKT	2024-12-03 21:54:19	2024-12-03 21:54:19
271	WPP-5aG0cg6GoF	ITC-7rUVFjBAdo	2024-12-03 21:54:23	2024-12-03 21:54:23
272	WPP-oDBSzhcLLI	ITC-stchngBjHP	2024-12-03 21:54:26	2024-12-03 21:54:26
273	WPP-0GlP65O7R7	ITC-pr2IoJIB0z	2024-12-03 21:54:29	2024-12-03 21:54:29
274	WPP-96LgFwsLdt	ITC-WHqNMXYAxy	2024-12-04 15:27:21	2024-12-04 15:27:21
275	WPP-vz3boHSLWM	ITC-7ApObtH2pi	2024-12-04 15:27:24	2024-12-04 15:27:24
276	WPP-DLhjQ2Lv7l	ITC-mFhh9aMM9V	2024-12-04 15:27:26	2024-12-04 15:27:26
277	WPP-zlTK6eR38j	ITC-XbiBgsSJkM	2024-12-04 15:27:29	2024-12-04 15:27:29
278	WPP-4oRXTE8RHb	ITC-NHnbmFh8Ej	2024-12-04 16:28:58	2024-12-04 16:28:58
279	WPP-Vputyy4PlW	ITC-QuCgap9eFI	2024-12-04 16:29:00	2024-12-04 16:29:00
280	WPP-BaKsbebaUC	ITC-ft4j6MrmEp	2024-12-04 16:29:03	2024-12-04 16:29:03
281	WPP-78WeXaHNPm	ITC-lcMghoXuxS	2024-12-04 16:29:06	2024-12-04 16:29:06
282	WPP-SNXl3CAMmJ	ITC-ze20g04VB2	2024-12-06 12:23:23	2024-12-06 12:23:23
283	WPP-hDs93Rcs6w	ITC-0KZCWKi2eL	2024-12-06 12:23:27	2024-12-06 12:23:27
284	WPP-tobQqzQ28Q	ITC-1e2g490fDv	2024-12-08 18:01:59	2024-12-08 18:01:59
285	WPP-sJNMAvj5Ai	ITC-xWYNRrs8yJ	2024-12-08 18:02:13	2024-12-08 18:02:13
286	WPP-YGsn9EDylK	ITC-1DHfDk80aX	2024-12-08 18:03:25	2024-12-08 18:03:25
287	WPP-EsklZ9gqjZ	ITC-DlaAptpq2c	2024-12-08 18:03:29	2024-12-08 18:03:29
288	WPP-ZwW1iscpoO	ITC-WHqNMXYAxy	2024-12-09 15:21:45	2024-12-09 15:21:45
289	WPP-qPks5hfWS0	ITC-7ApObtH2pi	2024-12-09 15:21:49	2024-12-09 15:21:49
290	WPP-TDz8fMM5zX	ITC-mFhh9aMM9V	2024-12-09 15:21:51	2024-12-09 15:21:51
291	WPP-Es9ccusY9c	ITC-XbiBgsSJkM	2024-12-09 15:21:56	2024-12-09 15:21:56
292	WPP-2f1GBwaRxf	ITC-SNfUA4zPX9	2024-12-09 16:24:07	2024-12-09 16:24:07
293	WPP-iYVyURtvN0	ITC-NK4e9qdvdM	2024-12-09 16:24:15	2024-12-09 16:24:15
294	WPP-pNPUoNL4XH	ITC-y9rHXdOAhj	2024-12-09 16:24:33	2024-12-09 16:24:33
295	WPP-Zcp0TZyOXJ	ITC-2KcILuMvKT	2024-12-09 17:34:14	2024-12-09 17:34:14
296	WPP-950fNBDQPO	ITC-7rUVFjBAdo	2024-12-09 17:34:17	2024-12-09 17:34:17
297	WPP-OyHxjzwqkr	ITC-stchngBjHP	2024-12-09 17:34:19	2024-12-09 17:34:19
298	WPP-owrQ66foCw	ITC-pr2IoJIB0z	2024-12-09 17:34:22	2024-12-09 17:34:22
299	WPP-wWbmMh6U3t	ITC-ze20g04VB2	2024-12-10 08:22:03	2024-12-10 08:22:03
300	WPP-9X5WP0IkrU	ITC-0KZCWKi2eL	2024-12-10 08:22:06	2024-12-10 08:22:06
301	WPP-l6yrARKI9o	ITC-NHnbmFh8Ej	2024-12-15 12:13:43	2024-12-15 12:13:43
302	WPP-1tOhL6nyFH	ITC-QuCgap9eFI	2024-12-15 12:13:56	2024-12-15 12:13:56
303	WPP-iANIr8yG3J	ITC-ft4j6MrmEp	2024-12-15 12:13:59	2024-12-15 12:13:59
304	WPP-ftgsF8H7ob	ITC-lcMghoXuxS	2024-12-15 12:14:05	2024-12-15 12:14:05
305	WPP-tOiL7uYcwP	ITC-GQFm3SnCSL	2024-12-15 14:34:49	2024-12-15 14:34:49
306	WPP-NBbU0tA0vy	ITC-e8x2GsZeYs	2024-12-15 14:34:52	2024-12-15 14:34:52
307	WPP-65C6cwus1D	ITC-J7EPLe7ruL	2024-12-15 15:38:16	2024-12-15 15:38:16
308	WPP-e7hnHcJMkA	ITC-GvO24AQJZE	2024-12-15 15:57:04	2024-12-15 15:57:04
309	WPP-DKkDecJS1t	ITC-3Rh4ox62hn	2024-12-15 17:16:02	2024-12-15 17:16:02
310	WPP-uR3EKSTEXu	ITC-xWYNRrs8yJ	2024-12-15 17:17:19	2024-12-15 17:17:19
311	WPP-ZV5cwTlc6h	ITC-1e2g490fDv	2024-12-15 17:17:22	2024-12-15 17:17:22
312	WPP-WgGqtAMHfW	ITC-1DHfDk80aX	2024-12-15 17:18:15	2024-12-15 17:18:15
313	WPP-2rTtRE8jvU	ITC-DlaAptpq2c	2024-12-15 17:18:18	2024-12-15 17:18:18
314	WPP-AlkfBwbWxR	ITC-gngFgoaoGi	2024-12-15 18:54:36	2024-12-15 18:54:36
315	WPP-fJazzD1tYz	ITC-3uK8WuMYbf	2024-12-15 18:57:41	2024-12-15 18:57:41
316	WPP-dpXRXr5Wi1	ITC-BKrKKpzteK	2024-12-15 19:02:19	2024-12-15 19:02:19
317	WPP-l7KCjBaYUl	ITC-zr840ljEPc	2024-12-15 19:05:27	2024-12-15 19:05:27
318	WPP-aGa3PTSo5W	ITC-lykjDQVfnF	2024-12-16 06:50:48	2024-12-16 06:50:48
319	WPP-J09xcHs53E	ITC-G5BiAoEKzU	2024-12-16 06:50:52	2024-12-16 06:50:52
320	WPP-j1DHnz362R	ITC-wKyyQSfDUp	2024-12-16 06:50:55	2024-12-16 06:50:55
321	WPP-Ty9HZbDidO	ITC-FIuyoYe0ta	2024-12-16 06:50:59	2024-12-16 06:50:59
322	WPP-lDSA7d4AwV	ITC-0KZCWKi2eL	2024-12-16 10:39:47	2024-12-16 10:39:47
323	WPP-AN7oGTgJY0	ITC-ze20g04VB2	2024-12-16 10:39:50	2024-12-16 10:39:50
324	WPP-OogzKfgU7a	ITC-SNfUA4zPX9	2024-12-16 11:27:47	2024-12-16 11:27:47
325	WPP-OuHfQszVVS	ITC-NK4e9qdvdM	2024-12-16 11:27:54	2024-12-16 11:27:54
326	WPP-UicI6lULyA	ITC-y9rHXdOAhj	2024-12-16 11:28:04	2024-12-16 11:28:04
327	WPP-ZhmGFmr3py	ITC-NHnbmFh8Ej	2024-12-17 07:49:35	2024-12-17 07:49:35
328	WPP-7uDZqVujhI	ITC-QuCgap9eFI	2024-12-17 07:49:39	2024-12-17 07:49:39
329	WPP-0ZPL09ZyFV	ITC-ft4j6MrmEp	2024-12-17 07:49:42	2024-12-17 07:49:42
330	WPP-sEpg9Tl4HN	ITC-lcMghoXuxS	2024-12-17 07:49:50	2024-12-17 07:49:50
331	WPP-nlrNWkkgrR	ITC-WHqNMXYAxy	2024-12-20 16:13:26	2024-12-20 16:13:26
332	WPP-rRWCIlrMQo	ITC-7ApObtH2pi	2024-12-20 16:13:29	2024-12-20 16:13:29
333	WPP-i1PysVEhO6	ITC-mFhh9aMM9V	2024-12-20 16:13:31	2024-12-20 16:13:31
334	WPP-uuLCEKiGLv	ITC-XbiBgsSJkM	2024-12-20 16:13:34	2024-12-20 16:13:34
335	WPP-hEOBQs73An	ITC-xWYNRrs8yJ	2024-12-22 14:24:17	2024-12-22 14:24:17
336	WPP-Re6XBi9i5R	ITC-3Rh4ox62hn	2024-12-22 14:24:26	2024-12-22 14:24:26
337	WPP-bWib8YnKex	ITC-1e2g490fDv	2024-12-22 14:24:40	2024-12-22 14:24:40
338	WPP-oqNfP0mt6Q	ITC-1DHfDk80aX	2024-12-22 14:25:21	2024-12-22 14:25:21
339	WPP-QtvudWobYR	ITC-DlaAptpq2c	2024-12-22 14:25:24	2024-12-22 14:25:24
340	WPP-T8ngC2unn2	ITC-2KcILuMvKT	2024-12-22 15:07:32	2024-12-22 15:07:32
341	WPP-Y80vRwDSt6	ITC-7rUVFjBAdo	2024-12-22 15:07:34	2024-12-22 15:07:34
342	WPP-JtJcBAec8T	ITC-stchngBjHP	2024-12-22 15:07:37	2024-12-22 15:07:37
343	WPP-q01vzsDgH4	ITC-pr2IoJIB0z	2024-12-22 15:07:40	2024-12-22 15:07:40
344	WPP-n513uU32Zb	ITC-D7q0DLwZ4y	2024-12-22 19:53:20	2024-12-22 19:53:20
345	WPP-4mafZr2L7o	ITC-XKDP9jfHOn	2024-12-22 19:54:24	2024-12-22 19:54:24
346	WPP-hSpxI3qL7G	ITC-0KZCWKi2eL	2024-12-23 09:56:39	2024-12-23 09:56:39
347	WPP-KeljEbZ9qx	ITC-ze20g04VB2	2024-12-23 09:56:42	2024-12-23 09:56:42
348	WPP-wMSPkL17tA	ITC-Igk7zIiFps	2024-12-23 10:23:55	2024-12-23 10:23:55
349	WPP-mRanara8DC	ITC-SNfUA4zPX9	2024-12-23 12:37:00	2024-12-23 12:37:00
350	WPP-S6gQIuyDaf	ITC-NK4e9qdvdM	2024-12-23 12:37:07	2024-12-23 12:37:07
351	WPP-SKcZULOwhM	ITC-y9rHXdOAhj	2024-12-23 12:37:19	2024-12-23 12:37:19
352	WPP-EuD1teAPvU	ITC-2KcILuMvKT	2024-12-27 20:46:22	2024-12-27 20:46:22
353	WPP-E8OUeUrrgh	ITC-7rUVFjBAdo	2024-12-27 20:46:24	2024-12-27 20:46:24
354	WPP-4SQ5NRCMx8	ITC-stchngBjHP	2024-12-27 20:46:27	2024-12-27 20:46:27
355	WPP-3NloEk9JWl	ITC-pr2IoJIB0z	2024-12-27 20:46:30	2024-12-27 20:46:30
356	WPP-FPM7lhOiux	ITC-ncLOsPXpV0	2024-12-29 06:22:36	2024-12-29 06:22:36
357	WPP-6Cw52cLXp0	ITC-N6BOPMHJ5z	2024-12-29 11:10:03	2024-12-29 11:10:03
358	WPP-15YapoOycp	ITC-GQFm3SnCSL	2024-12-29 11:12:33	2024-12-29 11:12:33
359	WPP-p6cc7v2GMJ	ITC-e8x2GsZeYs	2024-12-29 11:12:36	2024-12-29 11:12:36
360	WPP-nvgbFoHAzt	ITC-Igk7zIiFps	2024-12-30 06:10:54	2024-12-30 06:10:54
361	WPP-baxoRqBGEK	ITC-SNfUA4zPX9	2024-12-30 10:06:38	2024-12-30 10:06:38
362	WPP-4B7Zhx4F9I	ITC-NK4e9qdvdM	2024-12-30 10:06:43	2024-12-30 10:06:43
363	WPP-zwBnjsGBcw	ITC-y9rHXdOAhj	2024-12-30 10:06:47	2024-12-30 10:06:47
364	WPP-rhuB1a5tys	ITC-mFhh9aMM9V	2024-12-30 14:53:54	2024-12-30 14:53:54
365	WPP-Wi1smOxM9Y	ITC-2KcILuMvKT	2024-12-31 14:30:55	2024-12-31 14:30:55
366	WPP-HC8iav3jcY	ITC-7rUVFjBAdo	2024-12-31 14:30:57	2024-12-31 14:30:57
367	WPP-gr4tD7gw2C	ITC-stchngBjHP	2024-12-31 14:31:00	2024-12-31 14:31:00
368	WPP-W2k0hVUjjl	ITC-pr2IoJIB0z	2024-12-31 14:31:03	2024-12-31 14:31:03
369	WPP-GbxTHRGqE0	ITC-MmPRFIosoQ	2025-01-04 16:00:06	2025-01-04 16:00:06
370	WPP-GrpICAojf2	ITC-uzsAOBkQp1	2025-01-05 13:59:21	2025-01-05 13:59:21
371	WPP-5sB5EsBxx9	ITC-3Rh4ox62hn	2025-01-05 16:31:24	2025-01-05 16:31:24
372	WPP-DqiXFjPDTs	ITC-xWYNRrs8yJ	2025-01-05 16:34:16	2025-01-05 16:34:16
373	WPP-8pWoidQus8	ITC-1e2g490fDv	2025-01-05 16:34:21	2025-01-05 16:34:21
374	WPP-RnyLzdYSFK	ITC-1DHfDk80aX	2025-01-05 16:35:55	2025-01-05 16:35:55
375	WPP-4KYmQPkuwr	ITC-DlaAptpq2c	2025-01-05 16:35:57	2025-01-05 16:35:57
376	WPP-2OozL1YR5u	ITC-BKrKKpzteK	2025-01-05 18:30:30	2025-01-05 18:30:30
377	WPP-iu9oNLSGYt	ITC-3uK8WuMYbf	2025-01-05 18:34:39	2025-01-05 18:34:39
378	WPP-EyaBVzsMkt	ITC-gngFgoaoGi	2025-01-05 18:38:22	2025-01-05 18:38:22
379	WPP-XJBp5vXGcH	ITC-zr840ljEPc	2025-01-05 18:55:36	2025-01-05 18:55:36
380	WPP-mthHthcM4B	ITC-lWkgBm6M8y	2025-01-06 05:52:46	2025-01-06 05:52:46
381	WPP-5HUxdHRMp2	ITC-hKEcezNxWC	2025-01-06 05:52:51	2025-01-06 05:52:51
382	WPP-qxG2rEHGZw	ITC-SNfUA4zPX9	2025-01-06 09:17:17	2025-01-06 09:17:17
383	WPP-WM2LDAaZe1	ITC-NK4e9qdvdM	2025-01-06 09:17:25	2025-01-06 09:17:25
384	WPP-qo1jA2OCcU	ITC-y9rHXdOAhj	2025-01-06 09:17:31	2025-01-06 09:17:31
385	WPP-T6gyCNHOLJ	ITC-WHqNMXYAxy	2025-01-06 14:17:26	2025-01-06 14:17:26
386	WPP-w7d9aaObgQ	ITC-7ApObtH2pi	2025-01-06 14:17:28	2025-01-06 14:17:28
387	WPP-bWhdY2peCw	ITC-mFhh9aMM9V	2025-01-06 14:17:30	2025-01-06 14:17:30
388	WPP-hsRjS7avce	ITC-XbiBgsSJkM	2025-01-06 14:17:32	2025-01-06 14:17:32
389	WPP-tT3lyrEDqK	ITC-Igk7zIiFps	2025-01-06 17:16:00	2025-01-06 17:16:00
\.


--
-- Data for Name: package_profits; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_profits (id, uuid, amount, package_uuid, created_at, updated_at) FROM stdin;
1	PP-9mA4Y8HfXt	215.36260000	ITC-GQFm3SnCSL	2024-08-25 13:02:04	2024-08-25 13:02:04
2	PP-paljNAwe4K	179.98370000	ITC-e8x2GsZeYs	2024-08-25 13:02:04	2024-08-25 13:02:04
3	PP-JtQ8vO9XYQ	142.80000000	ITC-9qlOiI2JIo	2024-08-25 13:02:04	2024-08-25 13:02:04
4	PP-khCXFIFXa6	0.00000000	ITC-IzyqsIsBho	2024-08-25 13:02:04	2024-08-25 13:02:04
5	PP-cRZClXrs0l	885.59970000	ITC-luUiKG6k2m	2024-08-25 13:02:04	2024-08-25 13:02:04
6	PP-cKVlHkEznl	1967.24420000	ITC-8zlKdVTEKb	2024-08-25 13:02:04	2024-08-25 13:02:04
7	PP-A2c0BezfHu	1077.87320000	ITC-Z0CMEPUbwC	2024-08-25 13:02:04	2024-08-25 13:02:04
8	PP-ov3evrvuVf	440.33470000	ITC-5Z9isUEMMA	2024-08-25 13:02:04	2024-08-25 13:02:04
9	PP-QlZ8qMHjPg	862.56000000	ITC-FX6dgjpbdM	2024-08-25 13:02:04	2024-08-25 13:02:04
10	PP-dwpBlkGUID	99.36090000	ITC-FIuyoYe0ta	2024-08-25 13:02:04	2024-08-25 13:02:04
11	PP-Yfh8iFg8fp	778.85560000	ITC-lALXYPYWQ7	2024-08-25 13:02:04	2024-08-25 13:02:04
12	PP-0wF9GL9mbB	1278.72000000	ITC-dHT7IzMEbg	2024-08-25 13:02:04	2024-08-25 13:02:04
13	PP-TRpA9uZAw0	422.70910000	ITC-QRO4BEQ6wZ	2024-08-25 13:02:04	2024-08-25 13:02:04
14	PP-nVgdFpw8BE	372.35440000	ITC-WHqNMXYAxy	2024-08-25 13:02:04	2024-08-25 13:02:04
15	PP-MLLiQYsXww	157.71540000	ITC-7ApObtH2pi	2024-08-25 13:02:04	2024-08-25 13:02:04
16	PP-6bX6Fi9rYI	944.99590000	ITC-lWkgBm6M8y	2024-08-25 13:02:04	2024-08-25 13:02:04
17	PP-z8U4xJq9tH	8800.44480000	ITC-XKDP9jfHOn	2024-08-25 13:02:04	2024-08-25 13:02:04
18	PP-tunIB8OMEq	1230.17660000	ITC-D7q0DLwZ4y	2024-08-25 13:02:04	2024-08-25 13:02:04
19	PP-lWzHenEN6s	153.15300000	ITC-MmPRFIosoQ	2024-08-25 13:02:04	2024-08-25 13:02:04
20	PP-NNAhtumRIr	80.48420000	ITC-1h9KUgir50	2024-08-25 13:02:04	2024-08-25 13:02:04
21	PP-RUOAssBTkp	128.54740000	ITC-1DHfDk80aX	2024-08-25 13:02:04	2024-08-25 13:02:04
22	PP-Qn2XWR2LHe	23.87030000	ITC-DlaAptpq2c	2024-08-25 13:02:04	2024-08-25 13:02:04
23	PP-PF34u4xwvF	240.79730000	ITC-1e2g490fDv	2024-08-25 13:02:04	2024-08-25 13:02:04
24	PP-nd3Jl2cnch	56.57240000	ITC-xWYNRrs8yJ	2024-08-25 13:02:04	2024-08-25 13:02:04
25	PP-IVVyLhqAcj	5.65800000	ITC-5SKQtT3vyf	2024-08-25 13:02:04	2024-08-25 13:02:04
26	PP-CS0Mgm6E6H	0.00000000	ITC-4lqsfdy1G3	2024-08-25 13:02:04	2024-08-25 13:02:04
27	PP-ppox22dagE	114.11400000	ITC-BKrKKpzteK	2024-08-25 13:02:04	2024-08-25 13:02:04
28	PP-IEBI8o6Ue4	116.95920000	ITC-ncLOsPXpV0	2024-08-25 13:02:04	2024-08-25 13:02:04
29	PP-sfifubVQAb	114.00000000	ITC-gngFgoaoGi	2024-08-25 13:02:04	2024-08-25 13:02:04
30	PP-7eIwDttIAh	95.00000000	ITC-zr840ljEPc	2024-08-25 13:02:04	2024-08-25 13:02:04
31	PP-GunT1bilSc	126.87610000	ITC-p9IWpWM5vc	2024-08-25 13:02:04	2024-08-25 13:02:04
32	PP-DVgnyez57n	63.43820000	ITC-flY9k7HKFl	2024-08-25 13:02:04	2024-08-25 13:02:04
33	PP-jTdNeKa1cQ	29.59410000	ITC-ft4j6MrmEp	2024-08-25 13:02:04	2024-08-25 13:02:04
34	PP-Rf1cRMPzsQ	0.00000000	ITC-lcMghoXuxS	2024-08-25 13:02:04	2024-08-25 13:02:04
35	PP-6DiRDVzHhV	599.41530000	ITC-NHnbmFh8Ej	2024-08-25 13:02:04	2024-08-25 13:02:04
36	PP-edFBgj49Sw	81.41430000	ITC-ncZ6SSLhOc	2024-08-25 13:02:04	2024-08-25 13:02:04
37	PP-jtLk66mmBK	200.35200000	ITC-3uK8WuMYbf	2024-08-25 13:02:04	2024-08-25 13:02:04
38	PP-jk3tNC34Z8	25.00000000	ITC-wi4u5b0jdJ	2024-08-25 13:02:04	2024-08-25 13:02:04
39	PP-NGesb9nSXx	2.73333000	ITC-GQFm3SnCSL	2024-08-29 11:36:27	2024-08-29 11:36:27
40	PP-qd1VXgNEt7	2.76339663	ITC-e8x2GsZeYs	2024-08-29 11:36:27	2024-08-29 11:36:27
41	PP-VIpKbHKP2o	2.18666400	ITC-IzyqsIsBho	2024-08-29 11:36:27	2024-08-29 11:36:27
42	PP-zdjb4m047s	2.73333000	ITC-luUiKG6k2m	2024-08-29 11:36:27	2024-08-29 11:36:27
43	PP-diQlwMO7fj	4.09999500	ITC-8zlKdVTEKb	2024-08-29 11:36:27	2024-08-29 11:36:27
44	PP-G1xLl67cfm	2.73333000	ITC-Z0CMEPUbwC	2024-08-29 11:36:27	2024-08-29 11:36:27
45	PP-qwUlvyGr1s	1.36666500	ITC-5Z9isUEMMA	2024-08-29 11:36:27	2024-08-29 11:36:27
46	PP-tCt1Me1wJJ	2.18666400	ITC-FX6dgjpbdM	2024-08-29 11:36:27	2024-08-29 11:36:27
47	PP-FOka1t64Cm	1.36666500	ITC-FIuyoYe0ta	2024-08-29 11:36:27	2024-08-29 11:36:27
48	PP-Eo1CQ5a0R2	1.91333100	ITC-lALXYPYWQ7	2024-08-29 11:36:27	2024-08-29 11:36:27
49	PP-07QmAkIB51	2.73333000	ITC-dHT7IzMEbg	2024-08-29 11:36:27	2024-08-29 11:36:27
50	PP-NHmwaGTWgx	1.09333200	ITC-QRO4BEQ6wZ	2024-08-29 11:36:27	2024-08-29 11:36:27
51	PP-AIQDIJLfrr	1.70286459	ITC-WHqNMXYAxy	2024-08-29 11:36:27	2024-08-29 11:36:27
52	PP-W80QMQwYk8	0.95666550	ITC-7ApObtH2pi	2024-08-29 11:36:27	2024-08-29 11:36:27
53	PP-Meqp29WPBI	1.12613196	ITC-lWkgBm6M8y	2024-08-29 11:36:27	2024-08-29 11:36:27
54	PP-g4urNlN5cU	8.19999000	ITC-XKDP9jfHOn	2024-08-29 11:36:27	2024-08-29 11:36:27
55	PP-4O3V2lfYXi	3.00666300	ITC-D7q0DLwZ4y	2024-08-29 11:36:27	2024-08-29 11:36:27
56	PP-ljeQIWPX2a	0.74619909	ITC-MmPRFIosoQ	2024-08-29 11:36:27	2024-08-29 11:36:27
57	PP-LlltxLEmtz	0.58219929	ITC-1h9KUgir50	2024-08-29 11:36:27	2024-08-29 11:36:27
58	PP-dqoM0vjF3w	1.37759832	ITC-1DHfDk80aX	2024-08-29 11:36:27	2024-08-29 11:36:27
59	PP-hMu7tOzOOL	1.36666500	ITC-DlaAptpq2c	2024-08-29 11:36:27	2024-08-29 11:36:27
60	PP-mnzbS1WSEa	2.15659737	ITC-1e2g490fDv	2024-08-29 11:36:27	2024-08-29 11:36:27
61	PP-lkNjCwvIax	3.23899605	ITC-xWYNRrs8yJ	2024-08-29 11:36:27	2024-08-29 11:36:27
62	PP-ylLZD6O7kl	0.81999900	ITC-5SKQtT3vyf	2024-08-29 11:36:27	2024-08-29 11:36:27
63	PP-FlomduqI2R	3.00666300	ITC-4lqsfdy1G3	2024-08-29 11:36:27	2024-08-29 11:36:27
64	PP-MQbokKiShI	2.73606333	ITC-BKrKKpzteK	2024-08-29 11:36:27	2024-08-29 11:36:27
65	PP-1oXDmzZxcI	1.50333150	ITC-ncLOsPXpV0	2024-08-29 11:36:27	2024-08-29 11:36:27
66	PP-rVuJKDVkwI	2.73333000	ITC-gngFgoaoGi	2024-08-29 11:36:27	2024-08-29 11:36:27
67	PP-Lws50JAfnR	2.73333000	ITC-zr840ljEPc	2024-08-29 11:36:27	2024-08-29 11:36:27
68	PP-63XX4aF69h	2.73333000	ITC-p9IWpWM5vc	2024-08-29 11:36:27	2024-08-29 11:36:27
69	PP-DiHsKA81LZ	1.36666500	ITC-flY9k7HKFl	2024-08-29 11:36:27	2024-08-29 11:36:27
70	PP-db8kfa7RUU	1.38033165	ITC-ft4j6MrmEp	2024-08-29 11:36:27	2024-08-29 11:36:27
71	PP-1m8QMzSP0J	14.63151549	ITC-lcMghoXuxS	2024-08-29 11:36:27	2024-08-29 11:36:27
72	PP-2GwD5S9DO3	28.31456547	ITC-NHnbmFh8Ej	2024-08-29 11:36:27	2024-08-29 11:36:27
73	PP-rzFLSvRWwt	3.03672963	ITC-ncZ6SSLhOc	2024-08-29 11:36:27	2024-08-29 11:36:27
74	PP-ndETWtcDTD	5.46666000	ITC-3uK8WuMYbf	2024-08-29 11:36:27	2024-08-29 11:36:27
75	PP-TebGNy9eWB	1.50333150	ITC-wi4u5b0jdJ	2024-08-29 11:36:27	2024-08-29 11:36:27
76	PP-w95MHMZVpr	0.54666600	ITC-0KZCWKi2eL	2024-08-29 11:36:27	2024-08-29 11:36:27
77	PP-962JUB6pI5	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:10	2024-08-30 21:43:10
78	PP-zJY7TxOdUP	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:10	2024-08-30 21:43:10
79	PP-I8qr3emIUq	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:10	2024-08-30 21:43:10
80	PP-LMPV5kS9OM	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:10	2024-08-30 21:43:10
81	PP-DvYZCRgMKa	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:10	2024-08-30 21:43:10
82	PP-jeWpnQwntC	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:10	2024-08-30 21:43:10
83	PP-NFE2JJMpZj	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:10	2024-08-30 21:43:10
84	PP-yLW28ktNXN	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:10	2024-08-30 21:43:10
85	PP-IJ6lBQY06m	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:10	2024-08-30 21:43:10
86	PP-mr6QHV1OCa	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:10	2024-08-30 21:43:10
87	PP-5M6tXKHpM9	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:10	2024-08-30 21:43:10
88	PP-HZKBZADBUN	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:10	2024-08-30 21:43:10
89	PP-PSM3eaJV4L	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:10	2024-08-30 21:43:10
90	PP-Jl2YdaWwSK	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:10	2024-08-30 21:43:10
91	PP-ytPx1DjaaJ	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:10	2024-08-30 21:43:10
92	PP-J6NhYtFJ0K	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:10	2024-08-30 21:43:10
93	PP-YwC354nwqg	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:10	2024-08-30 21:43:10
94	PP-PGOVOUIxgi	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:10	2024-08-30 21:43:10
95	PP-qTnAyUuT0b	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:10	2024-08-30 21:43:10
96	PP-6BMGu4RLVg	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:10	2024-08-30 21:43:10
97	PP-Aq3OXNL1nB	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:10	2024-08-30 21:43:10
98	PP-QJzxAOqaG6	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:10	2024-08-30 21:43:10
99	PP-wWOaeZA6v2	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:10	2024-08-30 21:43:10
100	PP-Jdfu21E0Uf	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:10	2024-08-30 21:43:10
101	PP-wSeA7wJ63m	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:10	2024-08-30 21:43:10
102	PP-yDbQpA4Kp3	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:10	2024-08-30 21:43:10
103	PP-CpDag1irtG	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:10	2024-08-30 21:43:10
104	PP-z6N2mkugJH	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:10	2024-08-30 21:43:10
105	PP-z8Lki4T07z	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:10	2024-08-30 21:43:10
106	PP-qgZu3k8O9L	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:10	2024-08-30 21:43:10
107	PP-XnHcqin0jP	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:10	2024-08-30 21:43:10
108	PP-taF1XZan5o	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:10	2024-08-30 21:43:10
109	PP-s8wpou5jDU	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:10	2024-08-30 21:43:10
110	PP-I73i84s4f9	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:10	2024-08-30 21:43:10
111	PP-ZzSnfUBvBd	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:10	2024-08-30 21:43:10
112	PP-Nhm3dl8UI5	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:10	2024-08-30 21:43:10
113	PP-8DG3GeTqOx	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:10	2024-08-30 21:43:10
114	PP-U0RrowxYBL	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:10	2024-08-30 21:43:10
115	PP-FY6nHZ50Kn	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:14	2024-08-30 21:43:14
116	PP-05pN0mmyIY	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:14	2024-08-30 21:43:14
117	PP-wHZRw0OCpi	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:14	2024-08-30 21:43:14
118	PP-eKDclRjr0L	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:14	2024-08-30 21:43:14
119	PP-6KvEzXje9R	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:14	2024-08-30 21:43:14
120	PP-2u9zA0Q8A3	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:14	2024-08-30 21:43:14
121	PP-Wl93VLiLhg	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:14	2024-08-30 21:43:14
122	PP-agKW314apo	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:14	2024-08-30 21:43:14
123	PP-jmr4otW6AQ	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:14	2024-08-30 21:43:14
124	PP-Jv0oioLP7d	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:14	2024-08-30 21:43:14
125	PP-Ig5DsTfZx8	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:14	2024-08-30 21:43:14
126	PP-vs4nGMe7V9	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:14	2024-08-30 21:43:14
127	PP-DGv9ULFB7L	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:14	2024-08-30 21:43:14
128	PP-rQZeoyLv8F	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:14	2024-08-30 21:43:14
129	PP-0yaZopfv60	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:14	2024-08-30 21:43:14
130	PP-XNX3l8dRkJ	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:14	2024-08-30 21:43:14
131	PP-jJR9SjmF31	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:14	2024-08-30 21:43:14
132	PP-fTSralRvxm	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:14	2024-08-30 21:43:14
133	PP-9CdtjuJRiI	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:14	2024-08-30 21:43:14
134	PP-MZH7ldnZLp	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:14	2024-08-30 21:43:14
135	PP-btEfbwxn2K	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:14	2024-08-30 21:43:14
136	PP-KM9dGBXS66	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:14	2024-08-30 21:43:14
137	PP-33cCuS5CzU	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:14	2024-08-30 21:43:14
138	PP-0HF6iGm9Hx	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:14	2024-08-30 21:43:14
139	PP-Z5uVwzDcHd	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:14	2024-08-30 21:43:14
140	PP-uqLVviJFQO	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:14	2024-08-30 21:43:14
141	PP-Sg8rZK4Crz	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:14	2024-08-30 21:43:14
142	PP-90IBeFEjg4	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:14	2024-08-30 21:43:14
143	PP-TMoi0iPSL5	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:14	2024-08-30 21:43:14
144	PP-kzOllm8b7C	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:14	2024-08-30 21:43:14
145	PP-ekdhBE1M7K	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:14	2024-08-30 21:43:14
146	PP-eeRF6yyR7b	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:14	2024-08-30 21:43:14
147	PP-SGUeT5B2xb	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:14	2024-08-30 21:43:14
148	PP-G4OuK8Ayjc	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:14	2024-08-30 21:43:14
149	PP-cDOz0j4An4	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:14	2024-08-30 21:43:14
150	PP-f6zsF5K5lc	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:14	2024-08-30 21:43:14
151	PP-zqwXWVURQy	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:14	2024-08-30 21:43:14
152	PP-z53rVKu4wJ	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:14	2024-08-30 21:43:14
153	PP-JBqt33ijyt	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:17	2024-08-30 21:43:17
154	PP-VWbqq3UUWV	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:17	2024-08-30 21:43:17
155	PP-AHy1gcg8bs	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:17	2024-08-30 21:43:17
156	PP-uqqqgt4Zdy	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:17	2024-08-30 21:43:17
157	PP-uLUlQQ8FzT	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:17	2024-08-30 21:43:17
158	PP-mdsq4fumZL	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:17	2024-08-30 21:43:17
159	PP-oCZyEK1Gkx	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:17	2024-08-30 21:43:17
160	PP-CsF9T2ufyA	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:17	2024-08-30 21:43:17
161	PP-GDAJdLL9uQ	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:17	2024-08-30 21:43:17
162	PP-zXytoJhjkZ	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:17	2024-08-30 21:43:17
163	PP-9HiuCeBwDW	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:17	2024-08-30 21:43:17
164	PP-kgi3tARKXl	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:17	2024-08-30 21:43:17
165	PP-96cOhQjm9z	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:17	2024-08-30 21:43:17
166	PP-PvnA9e7q0v	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:17	2024-08-30 21:43:17
167	PP-FQyqQP3zXJ	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:17	2024-08-30 21:43:17
168	PP-MdGHAWa6RY	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:17	2024-08-30 21:43:17
169	PP-Q9FWYRECnP	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:17	2024-08-30 21:43:17
170	PP-O01Ym9ZAq2	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:17	2024-08-30 21:43:17
171	PP-wzSJIGbS8q	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:17	2024-08-30 21:43:17
172	PP-E9paGyx0eC	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:17	2024-08-30 21:43:17
173	PP-Tgbb4qZZAa	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:17	2024-08-30 21:43:17
174	PP-LQYCuDDB6L	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:17	2024-08-30 21:43:17
175	PP-MOC6CPqjlM	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:17	2024-08-30 21:43:17
176	PP-CFq19lFIiT	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:17	2024-08-30 21:43:17
177	PP-CeOQOj7Fz9	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:17	2024-08-30 21:43:17
178	PP-0iSWMJenMW	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:17	2024-08-30 21:43:17
179	PP-n1P0kmdE9J	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:17	2024-08-30 21:43:17
180	PP-8tktWbU9xC	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:17	2024-08-30 21:43:17
181	PP-UQKvGHY3Vh	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:17	2024-08-30 21:43:17
182	PP-ZviLCgdUDc	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:17	2024-08-30 21:43:17
183	PP-ZZleiw9BJR	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:17	2024-08-30 21:43:17
184	PP-5KA1GL4p84	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:17	2024-08-30 21:43:17
185	PP-jzwOvNq5DZ	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:17	2024-08-30 21:43:17
186	PP-qVP0w0ltVC	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:17	2024-08-30 21:43:17
187	PP-QUIKrGRsGw	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:17	2024-08-30 21:43:17
188	PP-dGwqdpuOPu	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:18	2024-08-30 21:43:18
189	PP-OErXVr6F3F	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:18	2024-08-30 21:43:18
190	PP-OlPdZ5Y3DF	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:18	2024-08-30 21:43:18
191	PP-RnpZMneJKn	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:20	2024-08-30 21:43:20
192	PP-qx8LkgDRwq	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:20	2024-08-30 21:43:20
193	PP-W73k3Xdvu6	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:20	2024-08-30 21:43:20
194	PP-BsT5ipMwAt	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:20	2024-08-30 21:43:20
195	PP-OfFvjaZcYO	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:20	2024-08-30 21:43:20
196	PP-GJedfSM1IO	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:20	2024-08-30 21:43:20
197	PP-ujqNInlRKW	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:20	2024-08-30 21:43:20
198	PP-Ebon3st3Fp	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:20	2024-08-30 21:43:20
199	PP-BYcPur0DxS	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:20	2024-08-30 21:43:20
200	PP-cBSrNGoBer	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:20	2024-08-30 21:43:20
201	PP-eFpXpLFDN7	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:20	2024-08-30 21:43:20
202	PP-MHRly7y1GI	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:20	2024-08-30 21:43:20
203	PP-D1voCBVPVK	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:20	2024-08-30 21:43:20
204	PP-5SCh9hxlVe	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:20	2024-08-30 21:43:20
205	PP-45TnRuWXZC	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:20	2024-08-30 21:43:20
206	PP-fJO0j32nk5	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:20	2024-08-30 21:43:20
207	PP-b4MtwMhYIt	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:20	2024-08-30 21:43:20
208	PP-a24rVOTBbd	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:20	2024-08-30 21:43:20
209	PP-G6vnddN811	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:20	2024-08-30 21:43:20
210	PP-VMFJcsGZRU	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:20	2024-08-30 21:43:20
211	PP-NMTe5qwxKi	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:20	2024-08-30 21:43:20
212	PP-0ts7HFLJpn	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:20	2024-08-30 21:43:20
213	PP-cmF4Gdtc2a	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:20	2024-08-30 21:43:20
214	PP-3l3dVnl7Ds	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:20	2024-08-30 21:43:20
215	PP-imArpnIiSJ	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:20	2024-08-30 21:43:20
216	PP-xiJnDX3ZGK	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:20	2024-08-30 21:43:20
217	PP-EyUNoeaMYE	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:20	2024-08-30 21:43:20
218	PP-MGZi6rb5yl	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:20	2024-08-30 21:43:20
219	PP-0vB3dhZstQ	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:20	2024-08-30 21:43:20
220	PP-cSNz7xgRjq	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:20	2024-08-30 21:43:20
221	PP-SjwCCCfN8C	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:20	2024-08-30 21:43:20
222	PP-2BU9gQ2eir	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:20	2024-08-30 21:43:20
223	PP-987ZJys8yZ	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:20	2024-08-30 21:43:20
224	PP-H2ButqeJDo	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:20	2024-08-30 21:43:20
225	PP-fdlRpKA1mj	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:20	2024-08-30 21:43:20
226	PP-uc9zrzBzIq	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:20	2024-08-30 21:43:20
227	PP-M3RwLlCuTa	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:20	2024-08-30 21:43:20
228	PP-9iKZtZR6r3	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:20	2024-08-30 21:43:20
229	PP-3j2iK4XpBk	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:23	2024-08-30 21:43:23
230	PP-TTcbvCNAwg	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:23	2024-08-30 21:43:23
231	PP-w9MUtynVLM	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:23	2024-08-30 21:43:23
232	PP-eBu8j3Lc36	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:23	2024-08-30 21:43:23
233	PP-G7804rX5I0	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:23	2024-08-30 21:43:23
234	PP-3K2SUHlps3	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:23	2024-08-30 21:43:23
235	PP-BBP2gkGuQ3	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:23	2024-08-30 21:43:23
236	PP-UW69dE18g0	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:23	2024-08-30 21:43:23
237	PP-y5dUT5oosE	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:23	2024-08-30 21:43:23
238	PP-VpPZzMY27B	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:23	2024-08-30 21:43:23
239	PP-ghqKvF4ot0	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:23	2024-08-30 21:43:23
240	PP-fWW6zWmlra	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:23	2024-08-30 21:43:23
241	PP-LqzMNZHvam	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:23	2024-08-30 21:43:23
242	PP-MmtOfDFFlF	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:23	2024-08-30 21:43:23
243	PP-FHRf4ucUsL	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:23	2024-08-30 21:43:23
244	PP-B4qRsDZhog	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:23	2024-08-30 21:43:23
245	PP-OyDgHcvaAR	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:23	2024-08-30 21:43:23
246	PP-6q1DzErAF7	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:23	2024-08-30 21:43:23
247	PP-Z1xqGrWpP2	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:23	2024-08-30 21:43:23
248	PP-8jmdEqBEEy	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:23	2024-08-30 21:43:23
249	PP-9BTd7V7gbU	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:23	2024-08-30 21:43:23
250	PP-osaan24ROJ	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:23	2024-08-30 21:43:23
251	PP-oLyA1J3dhE	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:23	2024-08-30 21:43:23
252	PP-eDOzgLS6cM	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:23	2024-08-30 21:43:23
253	PP-JolbEuxqtq	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:23	2024-08-30 21:43:23
254	PP-hXd8fYam6K	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:23	2024-08-30 21:43:23
255	PP-M4vNXszyhr	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:23	2024-08-30 21:43:23
256	PP-EHfFKAn3QH	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:23	2024-08-30 21:43:23
257	PP-wbd4ae0KVS	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:23	2024-08-30 21:43:23
258	PP-qPsvnimSh2	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:23	2024-08-30 21:43:23
259	PP-MSDGo3Q9sL	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:23	2024-08-30 21:43:23
260	PP-25saYNFfXT	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:23	2024-08-30 21:43:23
261	PP-ikcYwMqTW1	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:23	2024-08-30 21:43:23
262	PP-6qIwX15CQw	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:23	2024-08-30 21:43:23
263	PP-BDZit3gRvP	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:23	2024-08-30 21:43:23
264	PP-IsOgwHAXX7	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:23	2024-08-30 21:43:23
265	PP-0EtA4LB6A4	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:23	2024-08-30 21:43:23
266	PP-CMcbp1Et3h	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:23	2024-08-30 21:43:23
267	PP-ChBf14Up2E	2.73333000	ITC-GQFm3SnCSL	2024-08-30 21:43:26	2024-08-30 21:43:26
268	PP-oVkCDqgnoC	2.76339663	ITC-e8x2GsZeYs	2024-08-30 21:43:26	2024-08-30 21:43:26
269	PP-7ZMfozxaCy	2.18666400	ITC-IzyqsIsBho	2024-08-30 21:43:26	2024-08-30 21:43:26
270	PP-EviPMCyW7F	2.73333000	ITC-luUiKG6k2m	2024-08-30 21:43:26	2024-08-30 21:43:26
271	PP-HvSYb7HIUX	4.09999500	ITC-8zlKdVTEKb	2024-08-30 21:43:26	2024-08-30 21:43:26
272	PP-d7v8DSjWpY	2.73333000	ITC-Z0CMEPUbwC	2024-08-30 21:43:26	2024-08-30 21:43:26
273	PP-lQ9BDwBqCd	1.36666500	ITC-5Z9isUEMMA	2024-08-30 21:43:26	2024-08-30 21:43:26
274	PP-hFIklJyUTt	2.18666400	ITC-FX6dgjpbdM	2024-08-30 21:43:26	2024-08-30 21:43:26
275	PP-sqvMIxhe1s	1.36666500	ITC-FIuyoYe0ta	2024-08-30 21:43:26	2024-08-30 21:43:26
276	PP-I4A7Np0JYs	1.91333100	ITC-lALXYPYWQ7	2024-08-30 21:43:26	2024-08-30 21:43:26
277	PP-HWffdw4wJr	2.73333000	ITC-dHT7IzMEbg	2024-08-30 21:43:26	2024-08-30 21:43:26
278	PP-JswQXb7mmH	1.09333200	ITC-QRO4BEQ6wZ	2024-08-30 21:43:26	2024-08-30 21:43:26
279	PP-o7ecDicmkJ	1.70286459	ITC-WHqNMXYAxy	2024-08-30 21:43:26	2024-08-30 21:43:26
280	PP-OpZ1IhlaHE	0.95666550	ITC-7ApObtH2pi	2024-08-30 21:43:26	2024-08-30 21:43:26
281	PP-9yFm9xJ4Jy	1.12613196	ITC-lWkgBm6M8y	2024-08-30 21:43:26	2024-08-30 21:43:26
282	PP-5ITvBBsZIE	8.19999000	ITC-XKDP9jfHOn	2024-08-30 21:43:26	2024-08-30 21:43:26
283	PP-gfHnT8Vnb5	3.00666300	ITC-D7q0DLwZ4y	2024-08-30 21:43:26	2024-08-30 21:43:26
284	PP-5jb6D1ewLx	0.74619909	ITC-MmPRFIosoQ	2024-08-30 21:43:26	2024-08-30 21:43:26
285	PP-9XpeED3gTe	0.58219929	ITC-1h9KUgir50	2024-08-30 21:43:26	2024-08-30 21:43:26
286	PP-mppuLHiJu1	1.37759832	ITC-1DHfDk80aX	2024-08-30 21:43:26	2024-08-30 21:43:26
287	PP-Zb5GhSrcXo	1.36666500	ITC-DlaAptpq2c	2024-08-30 21:43:26	2024-08-30 21:43:26
288	PP-eVQgOHdcBm	2.15659737	ITC-1e2g490fDv	2024-08-30 21:43:26	2024-08-30 21:43:26
289	PP-sbwQ0xhPZU	3.23899605	ITC-xWYNRrs8yJ	2024-08-30 21:43:26	2024-08-30 21:43:26
290	PP-B0Ni0laeie	0.81999900	ITC-5SKQtT3vyf	2024-08-30 21:43:26	2024-08-30 21:43:26
291	PP-lcXiG8LqdA	3.00666300	ITC-4lqsfdy1G3	2024-08-30 21:43:26	2024-08-30 21:43:26
292	PP-2CtWexg5Ok	2.73606333	ITC-BKrKKpzteK	2024-08-30 21:43:26	2024-08-30 21:43:26
293	PP-KiWdE2kWnq	1.50333150	ITC-ncLOsPXpV0	2024-08-30 21:43:26	2024-08-30 21:43:26
294	PP-SdX5EvdVq4	2.73333000	ITC-gngFgoaoGi	2024-08-30 21:43:26	2024-08-30 21:43:26
295	PP-qwflAGaNwx	2.73333000	ITC-zr840ljEPc	2024-08-30 21:43:26	2024-08-30 21:43:26
296	PP-Dv1uv3cOp7	2.73333000	ITC-p9IWpWM5vc	2024-08-30 21:43:26	2024-08-30 21:43:26
297	PP-Yg0hd70cNY	1.36666500	ITC-flY9k7HKFl	2024-08-30 21:43:26	2024-08-30 21:43:26
298	PP-tLDY3wEC35	1.38033165	ITC-ft4j6MrmEp	2024-08-30 21:43:26	2024-08-30 21:43:26
299	PP-OundCRPIIn	14.63151549	ITC-lcMghoXuxS	2024-08-30 21:43:26	2024-08-30 21:43:26
300	PP-hTv4yIfOnF	28.31456547	ITC-NHnbmFh8Ej	2024-08-30 21:43:26	2024-08-30 21:43:26
301	PP-63jy8euf5F	3.03672963	ITC-ncZ6SSLhOc	2024-08-30 21:43:26	2024-08-30 21:43:26
302	PP-qp2m7gH7Rb	5.46666000	ITC-3uK8WuMYbf	2024-08-30 21:43:26	2024-08-30 21:43:26
303	PP-zbg6IItHlV	1.50333150	ITC-wi4u5b0jdJ	2024-08-30 21:43:26	2024-08-30 21:43:26
304	PP-3FwKs5QDVk	0.54666600	ITC-0KZCWKi2eL	2024-08-30 21:43:26	2024-08-30 21:43:26
305	PP-jTdjossgl	14.83000000	ITC-ft4j6MrmEp	\N	\N
306	PP-jTd1o5sgl	46.01000000	ITC-lcMghoXuxS	\N	\N
307	PP-jrd1o9Dgl	105.67000000	ITC-NHnbmFh8Ej	\N	\N
308	PP-8QcMxxxDJ4	22.24679063	ITC-GQFm3SnCSL	2024-09-04 17:29:45	2024-09-04 17:29:45
309	PP-R6RCweBgmj	21.90701365	ITC-e8x2GsZeYs	2024-09-04 17:29:45	2024-09-04 17:29:45
310	PP-KUqIPotc0f	14.81289600	ITC-IzyqsIsBho	2024-09-04 17:29:45	2024-09-04 17:29:45
311	PP-I859Ous1Dx	19.14987679	ITC-luUiKG6k2m	2024-09-04 17:29:45	2024-09-04 17:29:45
312	PP-0GpRdrp0c6	29.15711197	ITC-8zlKdVTEKb	2024-09-04 17:29:45	2024-09-04 17:29:45
313	PP-IfH34W8Bm8	19.22802778	ITC-Z0CMEPUbwC	2024-09-04 17:29:45	2024-09-04 17:29:45
314	PP-X967BYIDTH	9.54994071	ITC-5Z9isUEMMA	2024-09-04 17:29:45	2024-09-04 17:29:45
315	PP-SLyYDNVQLS	14.81289600	ITC-FX6dgjpbdM	2024-09-04 17:29:45	2024-09-04 17:29:45
316	PP-5JZGK03jw6	9.56663114	ITC-FIuyoYe0ta	2024-09-04 17:29:45	2024-09-04 17:29:45
317	PP-8J4WRInjUF	12.96128400	ITC-lALXYPYWQ7	2024-09-04 17:29:45	2024-09-04 17:29:45
318	PP-5Fxe8WWEzA	18.51612000	ITC-dHT7IzMEbg	2024-09-04 17:29:45	2024-09-04 17:29:45
319	PP-AHtpx0Mhxw	8.03661637	ITC-QRO4BEQ6wZ	2024-09-04 17:29:45	2024-09-04 17:29:45
320	PP-lCp8P4qHMf	17.97693799	ITC-WHqNMXYAxy	2024-09-04 17:29:45	2024-09-04 17:29:45
321	PP-cM2iDrac18	9.13982902	ITC-7ApObtH2pi	2024-09-04 17:29:45	2024-09-04 17:29:45
322	PP-RdfJu8xwl3	25.27226009	ITC-lWkgBm6M8y	2024-09-04 17:29:45	2024-09-04 17:29:45
323	PP-i2PUNEy11F	204.28816680	ITC-XKDP9jfHOn	2024-09-04 17:29:45	2024-09-04 17:29:45
324	PP-wWvcgK0nv6	43.53553168	ITC-D7q0DLwZ4y	2024-09-04 17:29:45	2024-09-04 17:29:45
325	PP-hhtTofW7jj	5.05490076	ITC-MmPRFIosoQ	2024-09-04 17:29:45	2024-09-04 17:29:45
326	PP-75e8EDR0kh	5.50964917	ITC-1h9KUgir50	2024-09-04 17:29:45	2024-09-04 17:29:45
327	PP-NjadVKAH67	11.43254684	ITC-1DHfDk80aX	2024-09-04 17:29:45	2024-09-04 17:29:45
328	PP-NkEDc2XoSw	9.70004534	ITC-DlaAptpq2c	2024-09-04 17:29:45	2024-09-04 17:29:45
329	PP-oewim4HI2W	18.53289746	ITC-1e2g490fDv	2024-09-04 17:29:45	2024-09-04 17:29:45
330	PP-Ob4sHhcOwR	22.98910355	ITC-xWYNRrs8yJ	2024-09-04 17:29:45	2024-09-04 17:29:45
331	PP-68V50wUwm6	5.55483600	ITC-5SKQtT3vyf	2024-09-04 17:29:45	2024-09-04 17:29:45
332	PP-bA4hpv78Gu	20.36773200	ITC-4lqsfdy1G3	2024-09-04 17:29:45	2024-09-04 17:29:45
333	PP-NwhA3oC550	18.53463612	ITC-BKrKKpzteK	2024-09-04 17:29:45	2024-09-04 17:29:45
334	PP-rHupv7epMg	12.34949658	ITC-ncLOsPXpV0	2024-09-04 17:29:45	2024-09-04 17:29:45
335	PP-dENE31bWzC	18.51612000	ITC-gngFgoaoGi	2024-09-04 17:29:45	2024-09-04 17:29:45
336	PP-tWh0KAe9yJ	18.51612000	ITC-zr840ljEPc	2024-09-04 17:29:45	2024-09-04 17:29:45
337	PP-gjEXX2A1mB	21.21964776	ITC-p9IWpWM5vc	2024-09-04 17:29:45	2024-09-04 17:29:45
338	PP-l7FZOg6ov1	10.60982666	ITC-flY9k7HKFl	2024-09-04 17:29:45	2024-09-04 17:29:45
339	PP-mHToDWenSK	9.89860851	ITC-ft4j6MrmEp	2024-09-04 17:29:45	2024-09-04 17:29:45
340	PP-kdoTAjSJp8	99.11679036	ITC-lcMghoXuxS	2024-09-04 17:29:45	2024-09-04 17:29:45
341	PP-8KSmt122e8	202.90733270	ITC-NHnbmFh8Ej	2024-09-04 17:29:45	2024-09-04 17:29:45
342	PP-eUXiyjDWOj	22.47248542	ITC-ncZ6SSLhOc	2024-09-04 17:29:45	2024-09-04 17:29:45
343	PP-acNEEARts2	37.03224000	ITC-3uK8WuMYbf	2024-09-04 17:29:45	2024-09-04 17:29:45
344	PP-6q6MbgO97l	10.84162007	ITC-wi4u5b0jdJ	2024-09-04 17:29:45	2024-09-04 17:29:45
345	PP-inIRW29MsI	3.70322400	ITC-0KZCWKi2eL	2024-09-04 17:29:45	2024-09-04 17:29:45
346	PP-97fyn7w78W	18.51612000	ITC-XbiBgsSJkM	2024-09-04 17:29:45	2024-09-04 17:29:45
347	PP-pr9YK3zfe0	1.85161200	ITC-2KcILuMvKT	2024-09-04 17:29:45	2024-09-04 17:29:45
348	PP-BnYxUBya1U	8.83218924	ITC-QuCgap9eFI	2024-09-04 17:29:45	2024-09-04 17:29:45
349	PP-iIDdbuHPsy	6.22910138	ITC-GQFm3SnCSL	2024-09-06 15:38:43	2024-09-06 15:38:43
350	PP-inXWGkaFjY	6.13396382	ITC-e8x2GsZeYs	2024-09-06 15:38:43	2024-09-06 15:38:43
351	PP-JJrGRLyTSg	4.14761088	ITC-IzyqsIsBho	2024-09-06 15:38:43	2024-09-06 15:38:43
352	PP-JReZWBE7ud	5.36196550	ITC-luUiKG6k2m	2024-09-06 15:38:43	2024-09-06 15:38:43
353	PP-yOvdCrgiCL	8.16399135	ITC-8zlKdVTEKb	2024-09-06 15:38:43	2024-09-06 15:38:43
354	PP-ro1fxhTH7X	5.38384778	ITC-Z0CMEPUbwC	2024-09-06 15:38:43	2024-09-06 15:38:43
355	PP-oS7JdasuHO	2.67398340	ITC-5Z9isUEMMA	2024-09-06 15:38:43	2024-09-06 15:38:43
356	PP-lMJIglto6z	4.14761088	ITC-FX6dgjpbdM	2024-09-06 15:38:43	2024-09-06 15:38:43
357	PP-FaDuf1ADcE	2.67865672	ITC-FIuyoYe0ta	2024-09-06 15:38:43	2024-09-06 15:38:43
358	PP-ytUTfMGWKS	3.62915952	ITC-lALXYPYWQ7	2024-09-06 15:38:43	2024-09-06 15:38:43
359	PP-OMu1OUVkFu	5.18451360	ITC-dHT7IzMEbg	2024-09-06 15:38:43	2024-09-06 15:38:43
360	PP-QkkxF0L2jo	2.25025258	ITC-QRO4BEQ6wZ	2024-09-06 15:38:43	2024-09-06 15:38:43
361	PP-68xs2ZOkui	5.03354264	ITC-WHqNMXYAxy	2024-09-06 15:38:43	2024-09-06 15:38:43
362	PP-xfLRT9A7YJ	2.60653769	ITC-7ApObtH2pi	2024-09-06 15:38:43	2024-09-06 15:38:43
363	PP-Tr1ZbDX7r7	7.07623282	ITC-lWkgBm6M8y	2024-09-06 15:38:43	2024-09-06 15:38:43
364	PP-oJqeUB107x	57.20068670	ITC-XKDP9jfHOn	2024-09-06 15:38:43	2024-09-06 15:38:43
365	PP-dpLTGcqtaF	12.18994887	ITC-D7q0DLwZ4y	2024-09-06 15:38:43	2024-09-06 15:38:43
366	PP-Tnaoycp0Mi	1.41537221	ITC-MmPRFIosoQ	2024-09-06 15:38:43	2024-09-06 15:38:43
367	PP-vbtqD6UM0o	1.54270177	ITC-1h9KUgir50	2024-09-06 15:38:43	2024-09-06 15:38:43
368	PP-yqswB0qqZa	3.20111312	ITC-1DHfDk80aX	2024-09-06 15:38:43	2024-09-06 15:38:43
369	PP-8WREDaZiF9	2.71601269	ITC-DlaAptpq2c	2024-09-06 15:38:43	2024-09-06 15:38:43
370	PP-12wkRys99L	5.18921129	ITC-1e2g490fDv	2024-09-06 15:38:43	2024-09-06 15:38:43
371	PP-LURqEh7L3T	6.43694899	ITC-xWYNRrs8yJ	2024-09-06 15:38:43	2024-09-06 15:38:43
372	PP-9zM10UcVV8	1.55535408	ITC-5SKQtT3vyf	2024-09-06 15:38:43	2024-09-06 15:38:43
373	PP-RTWyhRp267	5.70296496	ITC-4lqsfdy1G3	2024-09-06 15:38:43	2024-09-06 15:38:43
374	PP-ArsEuJ4kzj	5.18969811	ITC-BKrKKpzteK	2024-09-06 15:38:43	2024-09-06 15:38:43
375	PP-3y86lrvVkY	3.45785904	ITC-ncLOsPXpV0	2024-09-06 15:38:43	2024-09-06 15:38:43
376	PP-wc0QRfDjQp	5.18451360	ITC-gngFgoaoGi	2024-09-06 15:38:43	2024-09-06 15:38:43
377	PP-425kLMDMiF	5.18451360	ITC-zr840ljEPc	2024-09-06 15:38:43	2024-09-06 15:38:43
378	PP-Zq75xUwz6i	5.94150137	ITC-p9IWpWM5vc	2024-09-06 15:38:43	2024-09-06 15:38:43
379	PP-gOOdjB6isf	2.97075146	ITC-flY9k7HKFl	2024-09-06 15:38:43	2024-09-06 15:38:43
380	PP-3tmlZjHqnf	2.77161038	ITC-ft4j6MrmEp	2024-09-06 15:38:43	2024-09-06 15:38:43
381	PP-3udDXukXgh	27.75270130	ITC-lcMghoXuxS	2024-09-06 15:38:43	2024-09-06 15:38:43
382	PP-RGxF9RchvE	56.81405316	ITC-NHnbmFh8Ej	2024-09-06 15:38:43	2024-09-06 15:38:43
383	PP-ixzMR7h5QH	6.29229592	ITC-ncZ6SSLhOc	2024-09-06 15:38:43	2024-09-06 15:38:43
384	PP-BwImzSTp5U	10.36902720	ITC-3uK8WuMYbf	2024-09-06 15:38:43	2024-09-06 15:38:43
385	PP-O9PIhyeFYn	3.03565362	ITC-wi4u5b0jdJ	2024-09-06 15:38:43	2024-09-06 15:38:43
386	PP-qeKQly10f9	1.03690272	ITC-0KZCWKi2eL	2024-09-06 15:38:43	2024-09-06 15:38:43
387	PP-rQGLN4YixP	5.18451360	ITC-XbiBgsSJkM	2024-09-06 15:38:43	2024-09-06 15:38:43
388	PP-E38uTAFFET	0.52805107	ITC-2KcILuMvKT	2024-09-06 15:38:43	2024-09-06 15:38:43
389	PP-GQlPVfV6ih	2.47301299	ITC-QuCgap9eFI	2024-09-06 15:38:43	2024-09-06 15:38:43
390	PP-nZstA0s4Qg	5.11676184	ITC-GQFm3SnCSL	2024-09-09 11:28:53	2024-09-09 11:28:53
391	PP-laO16PBcQg	5.03861314	ITC-e8x2GsZeYs	2024-09-09 11:28:53	2024-09-09 11:28:53
392	PP-8MbxxZVvxZ	3.40696608	ITC-IzyqsIsBho	2024-09-09 11:28:53	2024-09-09 11:28:53
393	PP-1gqxgZXHt2	4.40447166	ITC-luUiKG6k2m	2024-09-09 11:28:53	2024-09-09 11:28:53
394	PP-l7IeozuHHw	6.70613575	ITC-8zlKdVTEKb	2024-09-09 11:28:53	2024-09-09 11:28:53
395	PP-U8lpxQDZkw	4.42244639	ITC-Z0CMEPUbwC	2024-09-09 11:28:53	2024-09-09 11:28:53
396	PP-tG5lvf8ujn	2.19648636	ITC-5Z9isUEMMA	2024-09-09 11:28:53	2024-09-09 11:28:53
397	PP-UphgLzqLWV	3.40696608	ITC-FX6dgjpbdM	2024-09-09 11:28:53	2024-09-09 11:28:53
398	PP-knW7kpecdo	2.25247426	ITC-FIuyoYe0ta	2024-09-09 11:28:53	2024-09-09 11:28:53
399	PP-AZ9S4yZ9NV	2.98109532	ITC-lALXYPYWQ7	2024-09-09 11:28:53	2024-09-09 11:28:53
400	PP-bSz08g4Var	4.25870760	ITC-dHT7IzMEbg	2024-09-09 11:28:53	2024-09-09 11:28:53
401	PP-dIky8WzUvC	1.84842177	ITC-QRO4BEQ6wZ	2024-09-09 11:28:53	2024-09-09 11:28:53
402	PP-wL58wRaQtq	4.23269065	ITC-WHqNMXYAxy	2024-09-09 11:28:53	2024-09-09 11:28:53
403	PP-SSm4nNduMt	2.15218502	ITC-7ApObtH2pi	2024-09-09 11:28:53	2024-09-09 11:28:53
404	PP-HqWlhGD33n	5.95038259	ITC-lWkgBm6M8y	2024-09-09 11:28:53	2024-09-09 11:28:53
405	PP-0521Yxnx4A	46.98627836	ITC-XKDP9jfHOn	2024-09-09 11:28:53	2024-09-09 11:28:53
406	PP-6wmQqWOxQu	10.25049081	ITC-D7q0DLwZ4y	2024-09-09 11:28:53	2024-09-09 11:28:53
407	PP-9xUgTOnedH	1.16262717	ITC-MmPRFIosoQ	2024-09-09 11:28:53	2024-09-09 11:28:53
408	PP-BKMkDfw8uE	1.29725321	ITC-1h9KUgir50	2024-09-09 11:28:53	2024-09-09 11:28:53
409	PP-aT4nlgLARr	2.62948577	ITC-1DHfDk80aX	2024-09-09 11:28:53	2024-09-09 11:28:53
410	PP-1EBq8b7xns	2.23101043	ITC-DlaAptpq2c	2024-09-09 11:28:53	2024-09-09 11:28:53
411	PP-iIgSuAaRqo	4.26256641	ITC-1e2g490fDv	2024-09-09 11:28:53	2024-09-09 11:28:53
412	PP-do9e2syUhn	5.28749382	ITC-xWYNRrs8yJ	2024-09-09 11:28:53	2024-09-09 11:28:53
413	PP-dSJbQp0fH4	1.27761228	ITC-5SKQtT3vyf	2024-09-09 11:28:53	2024-09-09 11:28:53
414	PP-gzgSKbovkR	4.68457836	ITC-4lqsfdy1G3	2024-09-09 11:28:53	2024-09-09 11:28:53
415	PP-jb657ZDHZX	4.26296631	ITC-BKrKKpzteK	2024-09-09 11:28:53	2024-09-09 11:28:53
416	PP-shkYzTNEUq	2.84038421	ITC-ncLOsPXpV0	2024-09-09 11:28:53	2024-09-09 11:28:53
417	PP-btx7zuZ4hW	4.25870760	ITC-gngFgoaoGi	2024-09-09 11:28:53	2024-09-09 11:28:53
418	PP-1gOmuKQ7A0	4.25870760	ITC-zr840ljEPc	2024-09-09 11:28:53	2024-09-09 11:28:53
419	PP-hZrB6SywSY	4.99619038	ITC-p9IWpWM5vc	2024-09-09 11:28:53	2024-09-09 11:28:53
420	PP-5CmaBvZUax	2.49809584	ITC-flY9k7HKFl	2024-09-09 11:28:53	2024-09-09 11:28:53
421	PP-RbTg1XQZhy	2.27667996	ITC-ft4j6MrmEp	2024-09-09 11:28:53	2024-09-09 11:28:53
422	PP-ThdBpP5LvH	22.79686178	ITC-lcMghoXuxS	2024-09-09 11:28:53	2024-09-09 11:28:53
423	PP-Y68XRCLMH3	46.66868652	ITC-NHnbmFh8Ej	2024-09-09 11:28:53	2024-09-09 11:28:53
424	PP-ghPS2qdB1P	5.29117244	ITC-ncZ6SSLhOc	2024-09-09 11:28:53	2024-09-09 11:28:53
425	PP-48H4fSUIba	8.51741520	ITC-3uK8WuMYbf	2024-09-09 11:28:53	2024-09-09 11:28:53
426	PP-f2iuYfdUt2	2.55267187	ITC-wi4u5b0jdJ	2024-09-09 11:28:53	2024-09-09 11:28:53
427	PP-7hG1QSDYBg	0.85615739	ITC-0KZCWKi2eL	2024-09-09 11:28:53	2024-09-09 11:28:53
428	PP-gjLXaPQjgs	4.35964167	ITC-XbiBgsSJkM	2024-09-09 11:28:53	2024-09-09 11:28:53
429	PP-7kzNJMCfQP	0.43600505	ITC-2KcILuMvKT	2024-09-09 11:28:53	2024-09-09 11:28:53
430	PP-rPtdFltgFC	2.03140353	ITC-QuCgap9eFI	2024-09-09 11:28:53	2024-09-09 11:28:53
431	PP-hylzedUtxh	9.04975365	ITC-G5BiAoEKzU	2024-09-09 11:28:53	2024-09-09 11:28:53
432	PP-LHdFOzvgoe	4.57811067	ITC-NK4e9qdvdM	2024-09-09 11:28:53	2024-09-09 11:28:53
433	PP-nNhMnfVybh	1.70348304	ITC-7rUVFjBAdo	2024-09-09 11:28:53	2024-09-09 11:28:53
434	PP-e5EonqUSC6	17.13002878	ITC-GQFm3SnCSL	2024-09-09 11:30:22	2024-09-09 11:30:22
435	PP-LDz5hHFRMx	16.86840051	ITC-e8x2GsZeYs	2024-09-09 11:30:22	2024-09-09 11:30:22
436	PP-uBytZnWluN	11.40592992	ITC-IzyqsIsBho	2024-09-09 11:30:22	2024-09-09 11:30:22
437	PP-fM1i57NZJl	14.74540513	ITC-luUiKG6k2m	2024-09-09 11:30:22	2024-09-09 11:30:22
438	PP-tu1vVhVH8U	22.45097622	ITC-8zlKdVTEKb	2024-09-09 11:30:22	2024-09-09 11:30:22
439	PP-x969p0aiBR	14.80558139	ITC-Z0CMEPUbwC	2024-09-09 11:30:22	2024-09-09 11:30:22
440	PP-PzvSyzcC8q	7.35345435	ITC-5Z9isUEMMA	2024-09-09 11:30:22	2024-09-09 11:30:22
441	PP-LqiiYguoCI	11.40592992	ITC-FX6dgjpbdM	2024-09-09 11:30:22	2024-09-09 11:30:22
442	PP-Gl9zXHtmQR	7.54089210	ITC-FIuyoYe0ta	2024-09-09 11:30:22	2024-09-09 11:30:22
443	PP-RZ5dBOhcuO	9.98018868	ITC-lALXYPYWQ7	2024-09-09 11:30:22	2024-09-09 11:30:22
444	PP-5vZc5CWYbz	14.25741240	ITC-dHT7IzMEbg	2024-09-09 11:30:22	2024-09-09 11:30:22
445	PP-U3dVIKNg1c	6.18819460	ITC-QRO4BEQ6wZ	2024-09-09 11:30:22	2024-09-09 11:30:22
446	PP-Df7nAwlUwa	14.17031217	ITC-WHqNMXYAxy	2024-09-09 11:30:22	2024-09-09 11:30:22
447	PP-2ZUBolryZm	7.20514114	ITC-7ApObtH2pi	2024-09-09 11:30:22	2024-09-09 11:30:22
448	PP-00IxILNClw	19.92084607	ITC-lWkgBm6M8y	2024-09-09 11:30:22	2024-09-09 11:30:22
449	PP-LlBGjqbLKW	157.30188844	ITC-XKDP9jfHOn	2024-09-09 11:30:22	2024-09-09 11:30:22
450	PP-Zf90smuqim	34.31686055	ITC-D7q0DLwZ4y	2024-09-09 11:30:22	2024-09-09 11:30:22
451	PP-tKeWDwgFtq	3.89227359	ITC-MmPRFIosoQ	2024-09-09 11:30:22	2024-09-09 11:30:22
452	PP-pYwIGtQ91h	4.34297814	ITC-1h9KUgir50	2024-09-09 11:30:22	2024-09-09 11:30:22
453	PP-ZlQLmYM9n1	8.80306107	ITC-1DHfDk80aX	2024-09-09 11:30:22	2024-09-09 11:30:22
454	PP-QVMbrkUAws	7.46903491	ITC-DlaAptpq2c	2024-09-09 11:30:22	2024-09-09 11:30:22
455	PP-4lubD9DKrV	14.27033104	ITC-1e2g490fDv	2024-09-09 11:30:22	2024-09-09 11:30:22
456	PP-iOZCL2EQNd	17.70160973	ITC-xWYNRrs8yJ	2024-09-09 11:30:22	2024-09-09 11:30:22
457	PP-xxgZ4H1AZh	4.27722372	ITC-5SKQtT3vyf	2024-09-09 11:30:22	2024-09-09 11:30:22
458	PP-xMwg9EUePP	15.68315364	ITC-4lqsfdy1G3	2024-09-09 11:30:22	2024-09-09 11:30:22
459	PP-WrukAWoCDQ	14.27166981	ITC-BKrKKpzteK	2024-09-09 11:30:22	2024-09-09 11:30:22
460	PP-oeiyK6bwNc	9.50911237	ITC-ncLOsPXpV0	2024-09-09 11:30:22	2024-09-09 11:30:22
461	PP-RJt9y34vsb	14.25741240	ITC-gngFgoaoGi	2024-09-09 11:30:22	2024-09-09 11:30:22
462	PP-d0y6wURb72	14.25741240	ITC-zr840ljEPc	2024-09-09 11:30:22	2024-09-09 11:30:22
463	PP-wrSwwC7AQ7	16.72637648	ITC-p9IWpWM5vc	2024-09-09 11:30:22	2024-09-09 11:30:22
464	PP-VHSiGVCFnw	8.36319043	ITC-flY9k7HKFl	2024-09-09 11:30:22	2024-09-09 11:30:22
465	PP-NX3KOy2rf8	7.62192855	ITC-ft4j6MrmEp	2024-09-09 11:30:22	2024-09-09 11:30:22
466	PP-y7F83unYjv	76.31992858	ITC-lcMghoXuxS	2024-09-09 11:30:22	2024-09-09 11:30:22
467	PP-jaNDz7O5dA	156.23864618	ITC-NHnbmFh8Ej	2024-09-09 11:30:22	2024-09-09 11:30:22
468	PP-5cVMBl2cE8	17.71392512	ITC-ncZ6SSLhOc	2024-09-09 11:30:22	2024-09-09 11:30:22
469	PP-zkCpAdZeXE	28.51482480	ITC-3uK8WuMYbf	2024-09-09 11:30:22	2024-09-09 11:30:22
470	PP-KNDjOuxmcr	8.54590146	ITC-wi4u5b0jdJ	2024-09-09 11:30:22	2024-09-09 11:30:22
471	PP-le7y3GwPt9	2.86626603	ITC-0KZCWKi2eL	2024-09-09 11:30:22	2024-09-09 11:30:22
472	PP-O2DMUKqTkk	14.59532211	ITC-XbiBgsSJkM	2024-09-09 11:30:22	2024-09-09 11:30:22
473	PP-ZnlGgSp8Yr	1.45966908	ITC-2KcILuMvKT	2024-09-09 11:30:22	2024-09-09 11:30:22
474	PP-AaxPhmX8RJ	6.80078571	ITC-QuCgap9eFI	2024-09-09 11:30:22	2024-09-09 11:30:22
475	PP-3sjZMBVO9H	30.29700135	ITC-G5BiAoEKzU	2024-09-09 11:30:22	2024-09-09 11:30:22
476	PP-djjWx0cjZD	15.32671833	ITC-NK4e9qdvdM	2024-09-09 11:30:22	2024-09-09 11:30:22
477	PP-NNrBzOKvZN	5.70296496	ITC-7rUVFjBAdo	2024-09-09 11:30:22	2024-09-09 11:30:22
478	PP-Hsuh9Pg2xc	7.62660000	ITC-GQFm3SnCSL	2024-09-16 14:32:07	2024-09-16 14:32:07
479	PP-8BNwaPKA6O	6.25843519	ITC-e8x2GsZeYs	2024-09-16 14:32:07	2024-09-16 14:32:07
480	PP-vSyufH7mSB	4.15484328	ITC-IzyqsIsBho	2024-09-16 14:32:07	2024-09-16 14:32:07
481	PP-qmc5JdYBeP	4.40447166	ITC-luUiKG6k2m	2024-09-16 14:32:07	2024-09-16 14:32:07
482	PP-uwwFEOp9Vn	6.70613575	ITC-8zlKdVTEKb	2024-09-16 14:32:07	2024-09-16 14:32:07
483	PP-l0qSSft7Va	4.42244639	ITC-Z0CMEPUbwC	2024-09-16 14:32:07	2024-09-16 14:32:07
484	PP-z6CoLgOKwA	2.19648636	ITC-5Z9isUEMMA	2024-09-16 14:32:07	2024-09-16 14:32:07
485	PP-gw6SarnJa0	3.40696608	ITC-FX6dgjpbdM	2024-09-16 14:32:07	2024-09-16 14:32:07
486	PP-JLyosDa4W3	2.74692419	ITC-FIuyoYe0ta	2024-09-16 14:32:07	2024-09-16 14:32:07
487	PP-ObUtACKth7	2.98109532	ITC-lALXYPYWQ7	2024-09-16 14:32:07	2024-09-16 14:32:07
488	PP-fZ7kk6f0gz	4.25870760	ITC-dHT7IzMEbg	2024-09-16 14:32:07	2024-09-16 14:32:07
489	PP-LokaO3cGN5	1.84842177	ITC-QRO4BEQ6wZ	2024-09-16 14:32:07	2024-09-16 14:32:07
490	PP-NXfVwAX4pC	5.25740305	ITC-WHqNMXYAxy	2024-09-16 14:32:07	2024-09-16 14:32:07
491	PP-wm7hllberB	2.67321782	ITC-7ApObtH2pi	2024-09-16 14:32:07	2024-09-16 14:32:07
492	PP-MCbb7GsJLQ	7.39093928	ITC-lWkgBm6M8y	2024-09-16 14:32:07	2024-09-16 14:32:07
493	PP-nncSi1FSXL	57.30043045	ITC-XKDP9jfHOn	2024-09-16 14:32:07	2024-09-16 14:32:07
494	PP-TCy8R7zRJy	10.44029013	ITC-D7q0DLwZ4y	2024-09-16 14:32:07	2024-09-16 14:32:07
495	PP-MQ9SkQSoNV	1.16262717	ITC-MmPRFIosoQ	2024-09-16 14:32:07	2024-09-16 14:32:07
496	PP-s0uop2boHb	1.32127331	ITC-1h9KUgir50	2024-09-16 14:32:07	2024-09-16 14:32:07
497	PP-P8eOhNpK16	3.20669506	ITC-1DHfDk80aX	2024-09-16 14:32:07	2024-09-16 14:32:07
498	PP-NCBAr49TN5	2.72074874	ITC-DlaAptpq2c	2024-09-16 14:32:07	2024-09-16 14:32:07
499	PP-CcEWBB7hil	5.19825998	ITC-1e2g490fDv	2024-09-16 14:32:07	2024-09-16 14:32:07
500	PP-YJqyCPsFLl	7.73780412	ITC-xWYNRrs8yJ	2024-09-16 14:32:07	2024-09-16 14:32:07
501	PP-LsDo1Ge1Hw	1.27761228	ITC-5SKQtT3vyf	2024-09-16 14:32:07	2024-09-16 14:32:07
502	PP-IzMEtWoZHN	5.71290951	ITC-4lqsfdy1G3	2024-09-16 14:32:07	2024-09-16 14:32:07
503	PP-QYxopd6Mgu	4.26296631	ITC-BKrKKpzteK	2024-09-16 14:32:07	2024-09-16 14:32:07
504	PP-dMF4bsZcFD	3.46388869	ITC-ncLOsPXpV0	2024-09-16 14:32:07	2024-09-16 14:32:07
505	PP-3hieswn0IW	6.23226170	ITC-gngFgoaoGi	2024-09-16 14:32:07	2024-09-16 14:32:07
506	PP-cid1VskeZK	6.23226170	ITC-zr840ljEPc	2024-09-16 14:32:07	2024-09-16 14:32:07
507	PP-jal9hHWTSI	6.20574209	ITC-p9IWpWM5vc	2024-09-16 14:32:07	2024-09-16 14:32:07
508	PP-1g74ByNiK6	3.10287186	ITC-flY9k7HKFl	2024-09-16 14:32:07	2024-09-16 14:32:07
509	PP-ktk2OqukFp	3.33173033	ITC-ft4j6MrmEp	2024-09-16 14:32:07	2024-09-16 14:32:07
510	PP-lHml2x7Ooa	33.36129688	ITC-lcMghoXuxS	2024-09-16 14:32:07	2024-09-16 14:32:07
511	PP-y1u9laYzHW	68.29571197	ITC-NHnbmFh8Ej	2024-09-16 14:32:07	2024-09-16 14:32:07
512	PP-mDxPeXGY95	5.38914442	ITC-ncZ6SSLhOc	2024-09-16 14:32:07	2024-09-16 14:32:07
513	PP-YivSs6uIbv	10.38710820	ITC-3uK8WuMYbf	2024-09-16 14:32:07	2024-09-16 14:32:07
514	PP-iYTnGtNe9v	3.17066045	ITC-wi4u5b0jdJ	2024-09-16 14:32:07	2024-09-16 14:32:07
515	PP-yD6rur1nYZ	0.90182105	ITC-0KZCWKi2eL	2024-09-16 14:32:07	2024-09-16 14:32:07
516	PP-PrxKYeQ3Fk	6.19412137	ITC-XbiBgsSJkM	2024-09-16 14:32:07	2024-09-16 14:32:07
517	PP-pFrnx1a5rj	0.50795879	ITC-2KcILuMvKT	2024-09-16 14:32:07	2024-09-16 14:32:07
518	PP-MNKiCPqbun	2.97278883	ITC-QuCgap9eFI	2024-09-16 14:32:07	2024-09-16 14:32:07
519	PP-JT3ne6ibj1	12.67227200	ITC-G5BiAoEKzU	2024-09-16 14:32:07	2024-09-16 14:32:07
520	PP-06tqJknRLI	4.57811067	ITC-NK4e9qdvdM	2024-09-16 14:32:07	2024-09-16 14:32:07
521	PP-mDsSWRodkp	1.73502494	ITC-7rUVFjBAdo	2024-09-16 14:32:07	2024-09-16 14:32:07
522	PP-pBO0cBreIN	0.85174152	ITC-wKyyQSfDUp	2024-09-16 14:32:07	2024-09-16 14:32:07
523	PP-Gz8NzKMNRO	0.63880614	ITC-mFhh9aMM9V	2024-09-16 14:32:07	2024-09-16 14:32:07
524	PP-rMELOG9AQ3	0.63880614	ITC-SNfUA4zPX9	2024-09-16 14:32:07	2024-09-16 14:32:07
525	PP-KaAsjebCGR	0.51935541	ITC-oa4hGuM7UC	2024-09-16 14:32:07	2024-09-16 14:32:07
526	PP-2ugi8EWhch	0.45994042	ITC-JGR3FfLxC6	2024-09-16 14:32:07	2024-09-16 14:32:07
527	PP-ODCPoSlxmi	25.53253045	ITC-GQFm3SnCSL	2024-09-16 14:40:18	2024-09-16 14:40:18
528	PP-QBzeGG24C4	20.95215258	ITC-e8x2GsZeYs	2024-09-16 14:40:19	2024-09-16 14:40:19
529	PP-rD32grUpZF	13.90969272	ITC-IzyqsIsBho	2024-09-16 14:40:19	2024-09-16 14:40:19
530	PP-43UsGyLlB3	14.74540513	ITC-luUiKG6k2m	2024-09-16 14:40:19	2024-09-16 14:40:19
531	PP-UjDmWS8UBW	22.45097622	ITC-8zlKdVTEKb	2024-09-16 14:40:19	2024-09-16 14:40:19
532	PP-GtvZYcRm4N	14.80558139	ITC-Z0CMEPUbwC	2024-09-16 14:40:19	2024-09-16 14:40:19
533	PP-l0XnUBTUSB	7.35345435	ITC-5Z9isUEMMA	2024-09-16 14:40:19	2024-09-16 14:40:19
534	PP-fY2XEZEm4r	11.40592992	ITC-FX6dgjpbdM	2024-09-16 14:40:19	2024-09-16 14:40:19
535	PP-lD9HiyYwqe	9.19622448	ITC-FIuyoYe0ta	2024-09-16 14:40:19	2024-09-16 14:40:19
536	PP-PPhoRHDMDS	9.98018868	ITC-lALXYPYWQ7	2024-09-16 14:40:19	2024-09-16 14:40:19
537	PP-MxavFQZecw	14.25741240	ITC-dHT7IzMEbg	2024-09-16 14:40:19	2024-09-16 14:40:19
538	PP-UCUOOvTTrk	6.18819460	ITC-QRO4BEQ6wZ	2024-09-16 14:40:19	2024-09-16 14:40:19
539	PP-iae1zh8APD	17.60087108	ITC-WHqNMXYAxy	2024-09-16 14:40:19	2024-09-16 14:40:19
540	PP-GSRQ6IsSxO	8.94946835	ITC-7ApObtH2pi	2024-09-16 14:40:19	2024-09-16 14:40:19
541	PP-LdupYXeNio	24.74357934	ITC-lWkgBm6M8y	2024-09-16 14:40:19	2024-09-16 14:40:19
542	PP-bkzNMX8VNE	191.83187585	ITC-XKDP9jfHOn	2024-09-16 14:40:19	2024-09-16 14:40:19
543	PP-asBIpbRBng	34.95227566	ITC-D7q0DLwZ4y	2024-09-16 14:40:19	2024-09-16 14:40:19
544	PP-GMtfvmieJP	3.89227359	ITC-MmPRFIosoQ	2024-09-16 14:40:19	2024-09-16 14:40:19
545	PP-moVqiBo2tb	4.42339324	ITC-1h9KUgir50	2024-09-16 14:40:19	2024-09-16 14:40:19
546	PP-3x2ylSs61j	10.73545737	ITC-1DHfDk80aX	2024-09-16 14:40:19	2024-09-16 14:40:19
547	PP-H9y5wzZTgv	9.10859362	ITC-DlaAptpq2c	2024-09-16 14:40:19	2024-09-16 14:40:19
548	PP-9FvWqWe8yU	17.40287037	ITC-1e2g490fDv	2024-09-16 14:40:19	2024-09-16 14:40:19
549	PP-MoyzBOAVQ7	25.90482248	ITC-xWYNRrs8yJ	2024-09-16 14:40:19	2024-09-16 14:40:19
550	PP-Y8EIV46VtS	4.27722372	ITC-5SKQtT3vyf	2024-09-16 14:40:19	2024-09-16 14:40:19
551	PP-MJO0d8LqNq	19.12582749	ITC-4lqsfdy1G3	2024-09-16 14:40:19	2024-09-16 14:40:19
552	PP-mCWTflDPvB	14.27166981	ITC-BKrKKpzteK	2024-09-16 14:40:19	2024-09-16 14:40:19
553	PP-SHlC0iiMqk	11.59649691	ITC-ncLOsPXpV0	2024-09-16 14:40:19	2024-09-16 14:40:19
554	PP-auuNsAdih3	20.86452830	ITC-gngFgoaoGi	2024-09-16 14:40:19	2024-09-16 14:40:19
555	PP-izeBh0Vw0e	20.86452830	ITC-zr840ljEPc	2024-09-16 14:40:19	2024-09-16 14:40:19
556	PP-sbgGK6LaX7	20.77574527	ITC-p9IWpWM5vc	2024-09-16 14:40:19	2024-09-16 14:40:19
557	PP-SpaIOA81Js	10.38787535	ITC-flY9k7HKFl	2024-09-16 14:40:19	2024-09-16 14:40:19
558	PP-PDUXlJ8jU2	11.15405373	ITC-ft4j6MrmEp	2024-09-16 14:40:19	2024-09-16 14:40:19
559	PP-FdEP6XAuMc	111.68781999	ITC-lcMghoXuxS	2024-09-16 14:40:19	2024-09-16 14:40:19
560	PP-FPnHbuQdZQ	228.64216615	ITC-NHnbmFh8Ej	2024-09-16 14:40:19	2024-09-16 14:40:19
561	PP-optJW1WcZB	18.04191829	ITC-ncZ6SSLhOc	2024-09-16 14:40:19	2024-09-16 14:40:19
562	PP-ANaaT0go2b	34.77423180	ITC-3uK8WuMYbf	2024-09-16 14:40:19	2024-09-16 14:40:19
563	PP-SI1CiAA0Pt	10.61481978	ITC-wi4u5b0jdJ	2024-09-16 14:40:19	2024-09-16 14:40:19
564	PP-ZULvQqr0uP	3.01914004	ITC-0KZCWKi2eL	2024-09-16 14:40:19	2024-09-16 14:40:19
565	PP-Il2wI6sbUD	20.73684110	ITC-XbiBgsSJkM	2024-09-16 14:40:19	2024-09-16 14:40:19
566	PP-nfJsOtcEXK	1.70055767	ITC-2KcILuMvKT	2024-09-16 14:40:19	2024-09-16 14:40:19
567	PP-da4T7t34lK	9.95238000	ITC-QuCgap9eFI	2024-09-16 14:40:19	2024-09-16 14:40:19
568	PP-l5vNMTRL8u	42.42456280	ITC-G5BiAoEKzU	2024-09-16 14:40:19	2024-09-16 14:40:19
569	PP-xPtoDt6Ntu	15.32671833	ITC-NK4e9qdvdM	2024-09-16 14:40:19	2024-09-16 14:40:19
570	PP-p9bpK3Fu7s	5.80856174	ITC-7rUVFjBAdo	2024-09-16 14:40:19	2024-09-16 14:40:19
571	PP-cQz1FANc9N	2.85148248	ITC-wKyyQSfDUp	2024-09-16 14:40:19	2024-09-16 14:40:19
572	PP-i8RqiwSRv1	2.13861186	ITC-mFhh9aMM9V	2024-09-16 14:40:19	2024-09-16 14:40:19
573	PP-4TdYOtuwyx	2.13861186	ITC-SNfUA4zPX9	2024-09-16 14:40:19	2024-09-16 14:40:19
574	PP-AfxAkYaHgM	1.73871159	ITC-oa4hGuM7UC	2024-09-16 14:40:19	2024-09-16 14:40:19
575	PP-OHVZxA0kpY	1.53980054	ITC-JGR3FfLxC6	2024-09-16 14:40:19	2024-09-16 14:40:19
576	PP-XlW5ZncDDs	1.56831536	ITC-YsOvuKKHva	2024-09-16 14:40:19	2024-09-16 14:40:19
577	PP-qCE7kTR4JV	36.47504350	ITC-GQFm3SnCSL	2024-09-24 08:20:52	2024-09-24 08:20:52
578	PP-WOEDsU7fJB	29.93164655	ITC-e8x2GsZeYs	2024-09-24 08:20:52	2024-09-24 08:20:52
579	PP-po54XRXBMp	19.87098960	ITC-IzyqsIsBho	2024-09-24 08:20:52	2024-09-24 08:20:52
580	PP-E44W0I3sJu	21.06486447	ITC-luUiKG6k2m	2024-09-24 08:20:52	2024-09-24 08:20:52
581	PP-Sr5UIIj9cT	32.07282317	ITC-8zlKdVTEKb	2024-09-24 08:20:52	2024-09-24 08:20:52
582	PP-idjvN50fZq	21.15083056	ITC-Z0CMEPUbwC	2024-09-24 08:20:52	2024-09-24 08:20:52
583	PP-NJD4hXESRz	10.50493478	ITC-5Z9isUEMMA	2024-09-24 08:20:52	2024-09-24 08:20:52
584	PP-jjtI4JSbEu	16.29418560	ITC-FX6dgjpbdM	2024-09-24 08:20:52	2024-09-24 08:20:52
585	PP-c440sq2hIh	13.13746354	ITC-FIuyoYe0ta	2024-09-24 08:20:52	2024-09-24 08:20:52
586	PP-qVTCBArwuf	14.25741240	ITC-lALXYPYWQ7	2024-09-24 08:20:52	2024-09-24 08:20:52
587	PP-1wRZKxg1rw	20.36773200	ITC-dHT7IzMEbg	2024-09-24 08:20:52	2024-09-24 08:20:52
588	PP-tEpTL6zTw9	8.84027801	ITC-QRO4BEQ6wZ	2024-09-24 08:20:52	2024-09-24 08:20:52
589	PP-l6Qkn38QOY	25.14410154	ITC-WHqNMXYAxy	2024-09-24 08:20:52	2024-09-24 08:20:52
590	PP-Y2PBllMxHR	12.78495479	ITC-7ApObtH2pi	2024-09-24 08:20:52	2024-09-24 08:20:52
591	PP-uQLTsjIG9V	36.14615135	ITC-lWkgBm6M8y	2024-09-24 08:20:52	2024-09-24 08:20:52
592	PP-caCgQZoYRD	274.04553693	ITC-XKDP9jfHOn	2024-09-24 08:20:52	2024-09-24 08:20:52
593	PP-AVQkcSXbeV	50.85636598	ITC-D7q0DLwZ4y	2024-09-24 08:20:52	2024-09-24 08:20:52
594	PP-DB9z79JDFd	5.56039084	ITC-MmPRFIosoQ	2024-09-24 08:20:52	2024-09-24 08:20:52
595	PP-p2oGrCZTXB	6.43613903	ITC-1h9KUgir50	2024-09-24 08:20:52	2024-09-24 08:20:52
596	PP-hV2um32SD2	15.33636768	ITC-1DHfDk80aX	2024-09-24 08:20:52	2024-09-24 08:20:52
597	PP-W4X2qFRTz4	13.01227660	ITC-DlaAptpq2c	2024-09-24 08:20:52	2024-09-24 08:20:52
598	PP-vLy8pAABY9	24.86124338	ITC-1e2g490fDv	2024-09-24 08:20:52	2024-09-24 08:20:52
599	PP-HSoI8mNF9j	37.00688925	ITC-xWYNRrs8yJ	2024-09-24 08:20:52	2024-09-24 08:20:52
600	PP-3Hz0eKxV6A	6.11031960	ITC-5SKQtT3vyf	2024-09-24 08:20:52	2024-09-24 08:20:52
601	PP-d1wqmnTzfC	27.32261070	ITC-4lqsfdy1G3	2024-09-24 08:20:52	2024-09-24 08:20:52
602	PP-6Jqo5abPVi	20.38809973	ITC-BKrKKpzteK	2024-09-24 08:20:52	2024-09-24 08:20:52
603	PP-vj2zUymgoM	16.56642416	ITC-ncLOsPXpV0	2024-09-24 08:20:52	2024-09-24 08:20:52
604	PP-qkp1rHy3Tz	29.80646900	ITC-gngFgoaoGi	2024-09-24 08:20:52	2024-09-24 08:20:52
605	PP-nlnUCg5Ps4	29.80646900	ITC-zr840ljEPc	2024-09-24 08:20:52	2024-09-24 08:20:52
606	PP-D2qG1sPkkI	30.34982217	ITC-p9IWpWM5vc	2024-09-24 08:20:52	2024-09-24 08:20:52
607	PP-cGTVoe81Gl	15.17491506	ITC-flY9k7HKFl	2024-09-24 08:20:52	2024-09-24 08:20:52
608	PP-bEzeiS7zcL	15.93436247	ITC-ft4j6MrmEp	2024-09-24 08:20:52	2024-09-24 08:20:52
609	PP-rsthBvYLbH	159.55402856	ITC-lcMghoXuxS	2024-09-24 08:20:52	2024-09-24 08:20:52
610	PP-7hiFy4b6AN	326.63166593	ITC-NHnbmFh8Ej	2024-09-24 08:20:52	2024-09-24 08:20:52
611	PP-nbXj20dSPl	26.25140659	ITC-ncZ6SSLhOc	2024-09-24 08:20:52	2024-09-24 08:20:52
612	PP-4zflCErUcA	49.67747400	ITC-3uK8WuMYbf	2024-09-24 08:20:52	2024-09-24 08:20:52
613	PP-ydN8QzvVJF	15.50644218	ITC-wi4u5b0jdJ	2024-09-24 08:20:52	2024-09-24 08:20:52
614	PP-4Ywf9yP68k	4.39291829	ITC-0KZCWKi2eL	2024-09-24 08:20:52	2024-09-24 08:20:52
615	PP-xiUzdNTzj2	29.62405871	ITC-XbiBgsSJkM	2024-09-24 08:20:52	2024-09-24 08:20:52
616	PP-lKAnVOywFx	2.47435057	ITC-2KcILuMvKT	2024-09-24 08:20:52	2024-09-24 08:20:52
617	PP-xIIgkTTvUB	14.21768571	ITC-QuCgap9eFI	2024-09-24 08:20:52	2024-09-24 08:20:52
618	PP-sFiowZNfP6	60.60651828	ITC-G5BiAoEKzU	2024-09-24 08:20:52	2024-09-24 08:20:52
619	PP-fBHIs2KIsj	22.70614435	ITC-NK4e9qdvdM	2024-09-24 08:20:52	2024-09-24 08:20:52
620	PP-E4esC9ACw8	8.29794535	ITC-7rUVFjBAdo	2024-09-24 08:20:52	2024-09-24 08:20:52
621	PP-dqrOxsRDWw	4.07354640	ITC-wKyyQSfDUp	2024-09-24 08:20:52	2024-09-24 08:20:52
622	PP-8Sw8FIk21u	5.09193300	ITC-mFhh9aMM9V	2024-09-24 08:20:52	2024-09-24 08:20:52
623	PP-3HcaOxdpEW	3.11172951	ITC-SNfUA4zPX9	2024-09-24 08:20:52	2024-09-24 08:20:52
624	PP-DFSc2OM2ho	2.48387370	ITC-oa4hGuM7UC	2024-09-24 08:20:52	2024-09-24 08:20:52
625	PP-OGAtjEN537	2.68258360	ITC-JGR3FfLxC6	2024-09-24 08:20:52	2024-09-24 08:20:52
626	PP-6to2CZNwJ0	2.27239355	ITC-YsOvuKKHva	2024-09-24 08:20:52	2024-09-24 08:20:52
627	PP-aXQTPTtGUR	2.19034590	ITC-stchngBjHP	2024-09-24 08:20:52	2024-09-24 08:20:52
628	PP-GScH332aGm	2.03677320	ITC-wIwSqATAWE	2024-09-24 08:20:52	2024-09-24 08:20:52
629	PP-VvaopljwZo	34.14748705	ITC-GQFm3SnCSL	2024-09-30 16:59:39	2024-09-30 16:59:39
630	PP-ikY0hu5cn5	27.88646440	ITC-e8x2GsZeYs	2024-09-30 16:59:39	2024-09-30 16:59:39
631	PP-Eu5pck3bpS	20.02940602	ITC-IzyqsIsBho	2024-09-30 16:59:39	2024-09-30 16:59:39
632	PP-KHEmSsM0Wq	19.14987679	ITC-luUiKG6k2m	2024-09-30 16:59:39	2024-09-30 16:59:39
633	PP-xFiuyvxP6l	29.15711197	ITC-8zlKdVTEKb	2024-09-30 16:59:39	2024-09-30 16:59:39
634	PP-6XCDzPnCnT	19.22802778	ITC-Z0CMEPUbwC	2024-09-30 16:59:39	2024-09-30 16:59:39
635	PP-wLLFN9poSN	9.54994071	ITC-5Z9isUEMMA	2024-09-30 16:59:40	2024-09-30 16:59:40
636	PP-vIeefk9TnC	14.81289600	ITC-FX6dgjpbdM	2024-09-30 16:59:40	2024-09-30 16:59:40
637	PP-dhsVYI2rZF	11.94314867	ITC-FIuyoYe0ta	2024-09-30 16:59:40	2024-09-30 16:59:40
638	PP-DtnjSJlgpr	12.96128400	ITC-lALXYPYWQ7	2024-09-30 16:59:40	2024-09-30 16:59:40
639	PP-DHMvYVCek8	18.51612000	ITC-dHT7IzMEbg	2024-09-30 16:59:40	2024-09-30 16:59:40
640	PP-Gi1s8zgFR6	8.03661637	ITC-QRO4BEQ6wZ	2024-09-30 16:59:40	2024-09-30 16:59:40
641	PP-UOLkkXBaYq	23.42604478	ITC-WHqNMXYAxy	2024-09-30 16:59:40	2024-09-30 16:59:40
642	PP-9eQYJuYPAp	11.91137902	ITC-7ApObtH2pi	2024-09-30 16:59:40	2024-09-30 16:59:40
643	PP-PPbddsmfee	33.67634190	ITC-lWkgBm6M8y	2024-09-30 16:59:40	2024-09-30 16:59:40
644	PP-uB45b86cxB	249.13230630	ITC-XKDP9jfHOn	2024-09-30 16:59:40	2024-09-30 16:59:40
645	PP-u653Nqe1Ar	47.17472256	ITC-D7q0DLwZ4y	2024-09-30 16:59:40	2024-09-30 16:59:40
646	PP-LU4QHf8q0S	5.05490076	ITC-MmPRFIosoQ	2024-09-30 16:59:40	2024-09-30 16:59:40
647	PP-CGtCuEntny	5.97020780	ITC-1h9KUgir50	2024-09-30 16:59:40	2024-09-30 16:59:40
648	PP-b2IaTdOP7W	13.94215243	ITC-1DHfDk80aX	2024-09-30 16:59:40	2024-09-30 16:59:40
649	PP-6P0GCnEThg	11.82934237	ITC-DlaAptpq2c	2024-09-30 16:59:40	2024-09-30 16:59:40
650	PP-rQGgvq9Bac	22.60113035	ITC-1e2g490fDv	2024-09-30 16:59:40	2024-09-30 16:59:40
651	PP-k77ZlXBcRF	33.64262659	ITC-xWYNRrs8yJ	2024-09-30 16:59:40	2024-09-30 16:59:40
652	PP-dp86L8sr0d	5.55483600	ITC-5SKQtT3vyf	2024-09-30 16:59:40	2024-09-30 16:59:40
653	PP-PkU3uahgqT	24.83873700	ITC-4lqsfdy1G3	2024-09-30 16:59:40	2024-09-30 16:59:40
654	PP-pXGaU6w5rt	18.53463612	ITC-BKrKKpzteK	2024-09-30 16:59:40	2024-09-30 16:59:40
655	PP-1SNLfpJzcs	15.06038560	ITC-ncLOsPXpV0	2024-09-30 16:59:40	2024-09-30 16:59:40
656	PP-sy0unnMkYq	27.09679000	ITC-gngFgoaoGi	2024-09-30 16:59:40	2024-09-30 16:59:40
657	PP-9Bd4dv4fOU	27.09679000	ITC-zr840ljEPc	2024-09-30 16:59:40	2024-09-30 16:59:40
658	PP-xQP4yTXaD0	28.27606674	ITC-p9IWpWM5vc	2024-09-30 16:59:40	2024-09-30 16:59:40
659	PP-vUW6jLQ0Xc	14.13803707	ITC-flY9k7HKFl	2024-09-30 16:59:40	2024-09-30 16:59:40
660	PP-34YCf8X9YJ	14.48578406	ITC-ft4j6MrmEp	2024-09-30 16:59:40	2024-09-30 16:59:40
661	PP-Ts6PQ0WHlp	145.04911687	ITC-lcMghoXuxS	2024-09-30 16:59:40	2024-09-30 16:59:40
662	PP-ucXAdR7hpf	296.93787812	ITC-NHnbmFh8Ej	2024-09-30 16:59:40	2024-09-30 16:59:40
663	PP-15ujaLc2rA	24.35098927	ITC-ncZ6SSLhOc	2024-09-30 16:59:40	2024-09-30 16:59:40
664	PP-ZqoeJGccBk	45.16134000	ITC-3uK8WuMYbf	2024-09-30 16:59:40	2024-09-30 16:59:40
665	PP-qdRsH98H9i	14.44691147	ITC-wi4u5b0jdJ	2024-09-30 16:59:40	2024-09-30 16:59:40
666	PP-coGRMVZVlW	4.07490188	ITC-0KZCWKi2eL	2024-09-30 16:59:40	2024-09-30 16:59:40
667	PP-KE4kPdASsv	27.59989356	ITC-XbiBgsSJkM	2024-09-30 16:59:40	2024-09-30 16:59:40
668	PP-EQl0ib9jlR	2.29522498	ITC-2KcILuMvKT	2024-09-30 16:59:40	2024-09-30 16:59:40
669	PP-MGtvNK0X9z	12.92516883	ITC-QuCgap9eFI	2024-09-30 16:59:40	2024-09-30 16:59:40
670	PP-sFetSDNLJq	56.46537059	ITC-G5BiAoEKzU	2024-09-30 16:59:40	2024-09-30 16:59:40
671	PP-zkPn51RhGY	21.06237910	ITC-NK4e9qdvdM	2024-09-30 16:59:40	2024-09-30 16:59:40
672	PP-kGrYWemLUS	7.69723243	ITC-7rUVFjBAdo	2024-09-30 16:59:40	2024-09-30 16:59:40
673	PP-IKS0mFrWE5	3.70322400	ITC-wKyyQSfDUp	2024-09-30 16:59:40	2024-09-30 16:59:40
674	PP-AxLZHIsOOE	4.62903000	ITC-mFhh9aMM9V	2024-09-30 16:59:40	2024-09-30 16:59:40
675	PP-m3LM0MNcWA	2.88646216	ITC-SNfUA4zPX9	2024-09-30 16:59:40	2024-09-30 16:59:40
676	PP-PXl3ypJ2ho	2.25806700	ITC-oa4hGuM7UC	2024-09-30 16:59:40	2024-09-30 16:59:40
677	PP-Bpc6TkASit	2.43871236	ITC-JGR3FfLxC6	2024-09-30 16:59:40	2024-09-30 16:59:40
678	PP-6njpAD5B1m	2.10788823	ITC-YsOvuKKHva	2024-09-30 16:59:40	2024-09-30 16:59:40
679	PP-Enc7jWRjWy	2.03178025	ITC-stchngBjHP	2024-09-30 16:59:40	2024-09-30 16:59:40
680	PP-EksF8WpGLP	1.88932514	ITC-wIwSqATAWE	2024-09-30 16:59:40	2024-09-30 16:59:40
681	PP-ExMI2Fai0G	1.85161200	ITC-ze20g04VB2	2024-09-30 16:59:40	2024-09-30 16:59:40
682	PP-yhQbelepPj	96.64526760	ITC-QXGzxMvmKL	2024-09-30 16:59:40	2024-09-30 16:59:40
683	PP-zyP6iekkZQ	1.96270872	ITC-YyyMDt1mKa	2024-09-30 16:59:40	2024-09-30 16:59:40
684	PP-ITfnpJCfKf	1.85161200	ITC-UYLbRC56Pw	2024-09-30 16:59:40	2024-09-30 16:59:40
685	PP-laG0qfbGsp	34.14748705	ITC-GQFm3SnCSL	2024-10-07 05:16:41	2024-10-07 05:16:41
686	PP-Z7hgzDHBQN	27.88646440	ITC-e8x2GsZeYs	2024-10-07 05:16:41	2024-10-07 05:16:41
687	PP-QKCTfe1aDH	20.02940602	ITC-IzyqsIsBho	2024-10-07 05:16:41	2024-10-07 05:16:41
688	PP-56jpJ08q31	19.14987679	ITC-luUiKG6k2m	2024-10-07 05:16:41	2024-10-07 05:16:41
689	PP-dw2XYFS2er	29.15711197	ITC-8zlKdVTEKb	2024-10-07 05:16:41	2024-10-07 05:16:41
690	PP-J5XsFjekTU	19.22802778	ITC-Z0CMEPUbwC	2024-10-07 05:16:41	2024-10-07 05:16:41
691	PP-kxHa18fNfR	9.54994071	ITC-5Z9isUEMMA	2024-10-07 05:16:41	2024-10-07 05:16:41
692	PP-DoALI3nYa3	14.81289600	ITC-FX6dgjpbdM	2024-10-07 05:16:41	2024-10-07 05:16:41
693	PP-Rd3qOC39js	11.94314867	ITC-FIuyoYe0ta	2024-10-07 05:16:41	2024-10-07 05:16:41
694	PP-wvuUIA7Uh5	12.96128400	ITC-lALXYPYWQ7	2024-10-07 05:16:41	2024-10-07 05:16:41
695	PP-vX5IYGob05	18.51612000	ITC-dHT7IzMEbg	2024-10-07 05:16:41	2024-10-07 05:16:41
696	PP-xt308inz8S	8.03661637	ITC-QRO4BEQ6wZ	2024-10-07 05:16:41	2024-10-07 05:16:41
697	PP-HNud9PjAzv	23.95502057	ITC-WHqNMXYAxy	2024-10-07 05:16:41	2024-10-07 05:16:41
698	PP-eKsR70g5YH	12.18034594	ITC-7ApObtH2pi	2024-10-07 05:16:41	2024-10-07 05:16:41
699	PP-C2SCnNDGWg	34.43677627	ITC-lWkgBm6M8y	2024-10-07 05:16:41	2024-10-07 05:16:41
700	PP-FUeipY1L8M	254.75788070	ITC-XKDP9jfHOn	2024-10-07 05:16:41	2024-10-07 05:16:41
701	PP-mvfGXnGHDh	47.17472256	ITC-D7q0DLwZ4y	2024-10-07 05:16:41	2024-10-07 05:16:41
702	PP-g2Q8W0QWRu	5.05490076	ITC-MmPRFIosoQ	2024-10-07 05:16:41	2024-10-07 05:16:41
703	PP-vGJZ0PyCge	6.08075289	ITC-1h9KUgir50	2024-10-07 05:16:41	2024-10-07 05:16:41
704	PP-RdECnc0GiO	13.94215243	ITC-1DHfDk80aX	2024-10-07 05:16:41	2024-10-07 05:16:41
705	PP-Adq44q7yVO	11.82934237	ITC-DlaAptpq2c	2024-10-07 05:16:41	2024-10-07 05:16:41
706	PP-3C9yiezMkp	22.60113035	ITC-1e2g490fDv	2024-10-07 05:16:41	2024-10-07 05:16:41
707	PP-dnEgyJrFzH	33.64262659	ITC-xWYNRrs8yJ	2024-10-07 05:16:41	2024-10-07 05:16:41
708	PP-ey6cHozJAm	5.55483600	ITC-5SKQtT3vyf	2024-10-07 05:16:41	2024-10-07 05:16:41
709	PP-tyfWrdTI3d	24.83873700	ITC-4lqsfdy1G3	2024-10-07 05:16:41	2024-10-07 05:16:41
710	PP-HEQzlEl2rZ	18.53463612	ITC-BKrKKpzteK	2024-10-07 05:16:41	2024-10-07 05:16:41
711	PP-gp8a6Je7AH	15.06038560	ITC-ncLOsPXpV0	2024-10-07 05:16:41	2024-10-07 05:16:41
712	PP-HC60emg6MR	27.09679000	ITC-gngFgoaoGi	2024-10-07 05:16:41	2024-10-07 05:16:41
713	PP-kFMGo6TUA0	27.09679000	ITC-zr840ljEPc	2024-10-07 05:16:41	2024-10-07 05:16:41
714	PP-GnDZWkcyvy	28.91455928	ITC-p9IWpWM5vc	2024-10-07 05:16:41	2024-10-07 05:16:41
715	PP-2QzkZWrXca	14.45728342	ITC-flY9k7HKFl	2024-10-07 05:16:41	2024-10-07 05:16:41
716	PP-VeWxwcDYux	14.48578406	ITC-ft4j6MrmEp	2024-10-07 05:16:41	2024-10-07 05:16:41
717	PP-88b04c6XTg	145.04911687	ITC-lcMghoXuxS	2024-10-07 05:16:41	2024-10-07 05:16:41
718	PP-jvMxIIfrXm	296.93787812	ITC-NHnbmFh8Ej	2024-10-07 05:16:41	2024-10-07 05:16:41
719	PP-h8QGhAnp9S	24.80187511	ITC-ncZ6SSLhOc	2024-10-07 05:16:41	2024-10-07 05:16:41
720	PP-p9sYGWDLNF	45.16134000	ITC-3uK8WuMYbf	2024-10-07 05:16:41	2024-10-07 05:16:41
721	PP-3MEXcowv1j	14.77313241	ITC-wi4u5b0jdJ	2024-10-07 05:16:41	2024-10-07 05:16:41
722	PP-myz58tlqxJ	4.15035325	ITC-0KZCWKi2eL	2024-10-07 05:16:41	2024-10-07 05:16:41
723	PP-hHcjyauEqL	28.22311765	ITC-XbiBgsSJkM	2024-10-07 05:16:41	2024-10-07 05:16:41
724	PP-pigJCpxG4G	2.29522498	ITC-2KcILuMvKT	2024-10-07 05:16:41	2024-10-07 05:16:41
725	PP-HeH5v9Vk9q	12.92516883	ITC-QuCgap9eFI	2024-10-07 05:16:41	2024-10-07 05:16:41
726	PP-00xEYIxyyF	56.46537059	ITC-G5BiAoEKzU	2024-10-07 05:16:41	2024-10-07 05:16:41
727	PP-m8bKA4qVmu	21.45237264	ITC-NK4e9qdvdM	2024-10-07 05:16:41	2024-10-07 05:16:41
728	PP-dPf944n4cB	7.69723243	ITC-7rUVFjBAdo	2024-10-07 05:16:41	2024-10-07 05:16:41
729	PP-iBo7NQFXqJ	3.70322400	ITC-wKyyQSfDUp	2024-10-07 05:16:41	2024-10-07 05:16:41
730	PP-CZ1H2fql8x	4.62903000	ITC-mFhh9aMM9V	2024-10-07 05:16:41	2024-10-07 05:16:41
731	PP-rKFvXQGVO3	2.93990824	ITC-SNfUA4zPX9	2024-10-07 05:16:41	2024-10-07 05:16:41
732	PP-44PVSvh2oj	2.25806700	ITC-oa4hGuM7UC	2024-10-07 05:16:41	2024-10-07 05:16:41
733	PP-diydJD4dJu	2.43871236	ITC-JGR3FfLxC6	2024-10-07 05:16:41	2024-10-07 05:16:41
734	PP-Dv0GwwyMYX	2.14492047	ITC-YsOvuKKHva	2024-10-07 05:16:41	2024-10-07 05:16:41
735	PP-BW6vzJVOLi	2.03178025	ITC-stchngBjHP	2024-10-07 05:16:41	2024-10-07 05:16:41
736	PP-TbxVjnjtdb	1.92430811	ITC-wIwSqATAWE	2024-10-07 05:16:41	2024-10-07 05:16:41
737	PP-1NCuCDezGG	1.88589667	ITC-ze20g04VB2	2024-10-07 05:16:41	2024-10-07 05:16:41
738	PP-bEmTO21bA8	98.82758249	ITC-QXGzxMvmKL	2024-10-07 05:16:41	2024-10-07 05:16:41
739	PP-iRYJyMTbcK	1.99905047	ITC-YyyMDt1mKa	2024-10-07 05:16:41	2024-10-07 05:16:41
740	PP-Tm1Mk3oxTQ	3.73750867	ITC-UYLbRC56Pw	2024-10-07 05:16:41	2024-10-07 05:16:41
741	PP-Mr15EUJmP2	2.07380544	ITC-hKEcezNxWC	2024-10-07 05:16:41	2024-10-07 05:16:41
742	PP-duht95dXZb	3.04839045	ITC-Dcs5Y9nS5L	2024-10-07 05:16:41	2024-10-07 05:16:41
743	PP-yvXgBDZSpV	35.07277434	ITC-GQFm3SnCSL	2024-10-14 11:45:59	2024-10-14 11:45:59
744	PP-wLwinzZxtX	28.51615946	ITC-e8x2GsZeYs	2024-10-14 11:45:59	2024-10-14 11:45:59
745	PP-jUBCds30gv	20.02940602	ITC-IzyqsIsBho	2024-10-14 11:45:59	2024-10-14 11:45:59
746	PP-kczfZuiayh	19.14987679	ITC-luUiKG6k2m	2024-10-14 11:45:59	2024-10-14 11:45:59
747	PP-2Etkzz8YvA	29.15711197	ITC-8zlKdVTEKb	2024-10-14 11:45:59	2024-10-14 11:45:59
748	PP-8UlRTJiCch	19.22802778	ITC-Z0CMEPUbwC	2024-10-14 11:45:59	2024-10-14 11:45:59
749	PP-adlAllPZeL	9.54994071	ITC-5Z9isUEMMA	2024-10-14 11:45:59	2024-10-14 11:45:59
750	PP-OWBn10tQSO	14.81289600	ITC-FX6dgjpbdM	2024-10-14 11:45:59	2024-10-14 11:45:59
751	PP-sTjvayEpfR	11.94314867	ITC-FIuyoYe0ta	2024-10-14 11:45:59	2024-10-14 11:45:59
752	PP-euwhIge7Pv	12.96128400	ITC-lALXYPYWQ7	2024-10-14 11:45:59	2024-10-14 11:45:59
753	PP-paSd6PrOLY	18.51612000	ITC-dHT7IzMEbg	2024-10-14 11:45:59	2024-10-14 11:45:59
754	PP-qioEPQWxpD	8.03661637	ITC-QRO4BEQ6wZ	2024-10-14 11:45:59	2024-10-14 11:45:59
755	PP-Tkbqyo4gNw	23.95502057	ITC-WHqNMXYAxy	2024-10-14 11:45:59	2024-10-14 11:45:59
756	PP-LPnrNsgflT	12.18034594	ITC-7ApObtH2pi	2024-10-14 11:45:59	2024-10-14 11:45:59
757	PP-3svLR6HuiW	35.21438175	ITC-lWkgBm6M8y	2024-10-14 11:45:59	2024-10-14 11:45:59
758	PP-vPgPIWFASL	260.51048433	ITC-XKDP9jfHOn	2024-10-14 11:45:59	2024-10-14 11:45:59
759	PP-JOzD7nVSBg	47.17472256	ITC-D7q0DLwZ4y	2024-10-14 11:45:59	2024-10-14 11:45:59
760	PP-oKFapoppBu	5.05490076	ITC-MmPRFIosoQ	2024-10-14 11:45:59	2024-10-14 11:45:59
761	PP-EfKTSaD1E2	6.19334484	ITC-1h9KUgir50	2024-10-14 11:45:59	2024-10-14 11:45:59
762	PP-MVDj1quiU2	13.94215243	ITC-1DHfDk80aX	2024-10-14 11:45:59	2024-10-14 11:45:59
763	PP-2PnbGVm7cZ	11.82934237	ITC-DlaAptpq2c	2024-10-14 11:45:59	2024-10-14 11:45:59
764	PP-cL5i4MFK1N	22.60113035	ITC-1e2g490fDv	2024-10-14 11:45:59	2024-10-14 11:45:59
765	PP-8xBm2tTzvz	33.64262659	ITC-xWYNRrs8yJ	2024-10-14 11:45:59	2024-10-14 11:45:59
766	PP-NJ35gSYtob	5.55483600	ITC-5SKQtT3vyf	2024-10-14 11:45:59	2024-10-14 11:45:59
767	PP-fBIbkztbHQ	24.83873700	ITC-4lqsfdy1G3	2024-10-14 11:45:59	2024-10-14 11:45:59
768	PP-adzU5qYuuz	18.53463612	ITC-BKrKKpzteK	2024-10-14 11:45:59	2024-10-14 11:45:59
769	PP-H95NMnWRV1	15.06038560	ITC-ncLOsPXpV0	2024-10-14 11:45:59	2024-10-14 11:45:59
770	PP-xy5VNHZWoW	27.09679000	ITC-gngFgoaoGi	2024-10-14 11:45:59	2024-10-14 11:45:59
771	PP-SmvcYTfFFd	27.09679000	ITC-zr840ljEPc	2024-10-14 11:45:59	2024-10-14 11:45:59
772	PP-CV9gYTum2v	29.56746940	ITC-p9IWpWM5vc	2024-10-14 11:45:59	2024-10-14 11:45:59
773	PP-liB7OvWyYi	14.78373857	ITC-flY9k7HKFl	2024-10-14 11:45:59	2024-10-14 11:45:59
774	PP-GBJAvNgaEC	14.48578406	ITC-ft4j6MrmEp	2024-10-14 11:45:59	2024-10-14 11:45:59
775	PP-oGBfIz3Jf9	145.04911687	ITC-lcMghoXuxS	2024-10-14 11:45:59	2024-10-14 11:45:59
776	PP-drEakWAZRy	296.93787812	ITC-NHnbmFh8Ej	2024-10-14 11:45:59	2024-10-14 11:45:59
777	PP-oHqgEX3qWX	25.26110961	ITC-ncZ6SSLhOc	2024-10-14 11:45:59	2024-10-14 11:45:59
778	PP-AiqJA61hhD	45.16134000	ITC-3uK8WuMYbf	2024-10-14 11:45:59	2024-10-14 11:45:59
779	PP-6NwbUu5ZVM	15.10671964	ITC-wi4u5b0jdJ	2024-10-14 11:46:00	2024-10-14 11:46:00
780	PP-WCT8bkQUI2	4.22720169	ITC-0KZCWKi2eL	2024-10-14 11:46:00	2024-10-14 11:46:00
781	PP-QzgDK0TD7a	28.22311765	ITC-XbiBgsSJkM	2024-10-14 11:46:00	2024-10-14 11:46:00
782	PP-BFYvAMHSKU	2.33772365	ITC-2KcILuMvKT	2024-10-14 11:46:00	2024-10-14 11:46:00
783	PP-v71eowpbRJ	12.92516883	ITC-QuCgap9eFI	2024-10-14 11:46:00	2024-10-14 11:46:00
784	PP-obymQzEAV7	56.46537059	ITC-G5BiAoEKzU	2024-10-14 11:46:00	2024-10-14 11:46:00
785	PP-iCvfNxGO3p	21.84958734	ITC-NK4e9qdvdM	2024-10-14 11:46:00	2024-10-14 11:46:00
786	PP-iintU8TjoD	7.83975531	ITC-7rUVFjBAdo	2024-10-14 11:46:00	2024-10-14 11:46:00
787	PP-59pcyezGDw	3.70322400	ITC-wKyyQSfDUp	2024-10-14 11:46:00	2024-10-14 11:46:00
788	PP-zBDFWYwbks	4.62903000	ITC-mFhh9aMM9V	2024-10-14 11:46:00	2024-10-14 11:46:00
789	PP-oNhD0HghBS	2.99434394	ITC-SNfUA4zPX9	2024-10-14 11:46:00	2024-10-14 11:46:00
790	PP-rb00kW01K7	2.25806700	ITC-oa4hGuM7UC	2024-10-14 11:46:00	2024-10-14 11:46:00
791	PP-QYtqSQtqGf	0.00000000	ITC-JGR3FfLxC6	2024-10-14 11:46:00	2024-10-14 11:46:00
792	PP-Y51TZx1lvC	2.18463607	ITC-YsOvuKKHva	2024-10-14 11:46:00	2024-10-14 11:46:00
793	PP-udYa3pUhQF	2.06940094	ITC-stchngBjHP	2024-10-14 11:46:00	2024-10-14 11:46:00
794	PP-HfFkhZgAbv	1.95993883	ITC-wIwSqATAWE	2024-10-14 11:46:00	2024-10-14 11:46:00
795	PP-hDvYwLbkv2	1.92081616	ITC-ze20g04VB2	2024-10-14 11:46:00	2024-10-14 11:46:00
796	PP-2Iv2bZ67Ry	101.05917552	ITC-QXGzxMvmKL	2024-10-14 11:46:00	2024-10-14 11:46:00
797	PP-De6Zd7Ufzc	2.03606513	ITC-YyyMDt1mKa	2024-10-14 11:46:00	2024-10-14 11:46:00
798	PP-6gZnikvMxv	3.80671283	ITC-UYLbRC56Pw	2024-10-14 11:46:00	2024-10-14 11:46:00
799	PP-qAs6uSUfDy	2.11220427	ITC-hKEcezNxWC	2024-10-14 11:46:00	2024-10-14 11:46:00
800	PP-ot32Rgr5nK	3.11722515	ITC-Dcs5Y9nS5L	2024-10-14 11:46:00	2024-10-14 11:46:00
801	PP-ZWK2fokuE2	1.85161200	ITC-tZg0t4X5xN	2024-10-14 11:46:00	2024-10-14 11:46:00
802	PP-rgwy71k06A	2.34838968	ITC-G5Ad3PJneB	2024-10-14 11:46:00	2024-10-14 11:46:00
803	PP-S0f5yXMohx	35.07277434	ITC-GQFm3SnCSL	2024-10-21 11:54:06	2024-10-21 11:54:06
804	PP-hTojs6uUfS	28.51615946	ITC-e8x2GsZeYs	2024-10-21 11:54:06	2024-10-21 11:54:06
805	PP-Vau4so9Jtu	21.38623824	ITC-IzyqsIsBho	2024-10-21 11:54:06	2024-10-21 11:54:06
806	PP-LqEEBVTWES	19.14987679	ITC-luUiKG6k2m	2024-10-21 11:54:06	2024-10-21 11:54:06
807	PP-3NtBZcdX0z	29.15711197	ITC-8zlKdVTEKb	2024-10-21 11:54:06	2024-10-21 11:54:06
808	PP-EA5AN6ni8C	19.22802778	ITC-Z0CMEPUbwC	2024-10-21 11:54:06	2024-10-21 11:54:06
809	PP-pxN7zM9VDe	9.54994071	ITC-5Z9isUEMMA	2024-10-21 11:54:06	2024-10-21 11:54:06
810	PP-dR9as4O1fS	14.81289600	ITC-FX6dgjpbdM	2024-10-21 11:54:06	2024-10-21 11:54:06
811	PP-7rDrdXTbC7	11.94314867	ITC-FIuyoYe0ta	2024-10-21 11:54:06	2024-10-21 11:54:06
812	PP-py5H4dkZwd	12.96128400	ITC-lALXYPYWQ7	2024-10-21 11:54:06	2024-10-21 11:54:06
813	PP-nhDyl6lfVi	18.51612000	ITC-dHT7IzMEbg	2024-10-21 11:54:06	2024-10-21 11:54:06
814	PP-4tz81AnzkS	8.03661637	ITC-QRO4BEQ6wZ	2024-10-21 11:54:06	2024-10-21 11:54:06
815	PP-cBIaZuS5mM	23.95502057	ITC-WHqNMXYAxy	2024-10-21 11:54:06	2024-10-21 11:54:06
816	PP-ObvOaFkW55	12.18034594	ITC-7ApObtH2pi	2024-10-21 11:54:06	2024-10-21 11:54:06
817	PP-9fOPfNnll7	36.00954608	ITC-lWkgBm6M8y	2024-10-21 11:54:06	2024-10-21 11:54:06
818	PP-BCfkUyGJlI	293.03220159	ITC-XKDP9jfHOn	2024-10-21 11:54:06	2024-10-21 11:54:06
819	PP-JuQDVyh9YP	48.04821538	ITC-D7q0DLwZ4y	2024-10-21 11:54:06	2024-10-21 11:54:06
820	PP-TXg9nuir36	5.05490076	ITC-MmPRFIosoQ	2024-10-21 11:54:06	2024-10-21 11:54:06
821	PP-DzC7SHAQDK	6.30802155	ITC-1h9KUgir50	2024-10-21 11:54:06	2024-10-21 11:54:06
822	PP-FXaLIA3H8W	13.94215243	ITC-1DHfDk80aX	2024-10-21 11:54:06	2024-10-21 11:54:06
823	PP-iZteP2IfR0	11.82934237	ITC-DlaAptpq2c	2024-10-21 11:54:06	2024-10-21 11:54:06
824	PP-vr8aYBYJgv	22.60113035	ITC-1e2g490fDv	2024-10-21 11:54:06	2024-10-21 11:54:06
825	PP-sfgUqYh5LY	33.64262659	ITC-xWYNRrs8yJ	2024-10-21 11:54:06	2024-10-21 11:54:06
826	PP-LE7kF8o15u	5.55483600	ITC-5SKQtT3vyf	2024-10-21 11:54:06	2024-10-21 11:54:06
827	PP-uYQNzrSw29	24.83873700	ITC-4lqsfdy1G3	2024-10-21 11:54:06	2024-10-21 11:54:06
828	PP-UNqE9wvAyh	18.53463612	ITC-BKrKKpzteK	2024-10-21 11:54:06	2024-10-21 11:54:06
829	PP-XbSoE3bxvk	15.06038560	ITC-ncLOsPXpV0	2024-10-21 11:54:06	2024-10-21 11:54:06
830	PP-TQzBUREcMI	27.09679000	ITC-gngFgoaoGi	2024-10-21 11:54:06	2024-10-21 11:54:06
831	PP-3zt4RfLlSu	27.09679000	ITC-zr840ljEPc	2024-10-21 11:54:06	2024-10-21 11:54:06
832	PP-gcVhvYlxuX	30.79963942	ITC-p9IWpWM5vc	2024-10-21 11:54:06	2024-10-21 11:54:06
833	PP-d0WN6GD2Df	15.11756529	ITC-flY9k7HKFl	2024-10-21 11:54:06	2024-10-21 11:54:06
834	PP-qkyrpO7698	14.48578406	ITC-ft4j6MrmEp	2024-10-21 11:54:06	2024-10-21 11:54:06
835	PP-pm5cmqEpSm	145.04911687	ITC-lcMghoXuxS	2024-10-21 11:54:06	2024-10-21 11:54:06
836	PP-ffy5zpCs24	296.93787812	ITC-NHnbmFh8Ej	2024-10-21 11:54:06	2024-10-21 11:54:06
837	PP-5RRGl9NqfN	32.61862977	ITC-ncZ6SSLhOc	2024-10-21 11:54:06	2024-10-21 11:54:06
838	PP-7ftoa51GoW	45.16134000	ITC-3uK8WuMYbf	2024-10-21 11:54:06	2024-10-21 11:54:06
839	PP-x7H6k9DqRe	15.40026835	ITC-wi4u5b0jdJ	2024-10-21 11:54:06	2024-10-21 11:54:06
840	PP-duWEzQ1ykk	4.30547307	ITC-0KZCWKi2eL	2024-10-21 11:54:06	2024-10-21 11:54:06
841	PP-mQpArf1G3D	28.22311765	ITC-XbiBgsSJkM	2024-10-21 11:54:06	2024-10-21 11:54:06
842	PP-TIGgO1oyBP	2.33772365	ITC-2KcILuMvKT	2024-10-21 11:54:06	2024-10-21 11:54:06
843	PP-SW5Bi8SJGZ	12.92516883	ITC-QuCgap9eFI	2024-10-21 11:54:06	2024-10-21 11:54:06
844	PP-wsZzqQHJGz	57.74039649	ITC-G5BiAoEKzU	2024-10-21 11:54:06	2024-10-21 11:54:06
845	PP-luQFUSPRfY	29.73603590	ITC-NK4e9qdvdM	2024-10-21 11:54:06	2024-10-21 11:54:06
846	PP-Q5aK1h209W	7.83975531	ITC-7rUVFjBAdo	2024-10-21 11:54:06	2024-10-21 11:54:06
847	PP-I69SiuZnyX	3.70322400	ITC-wKyyQSfDUp	2024-10-21 11:54:06	2024-10-21 11:54:06
848	PP-JprN4aNcKy	4.62903000	ITC-mFhh9aMM9V	2024-10-21 11:54:06	2024-10-21 11:54:06
849	PP-m5zSmQBKhj	3.04978757	ITC-SNfUA4zPX9	2024-10-21 11:54:06	2024-10-21 11:54:06
850	PP-u3Sc90IuZE	2.25806700	ITC-oa4hGuM7UC	2024-10-21 11:54:06	2024-10-21 11:54:06
851	PP-FJ60eDxcCh	0.00000000	ITC-JGR3FfLxC6	2024-10-21 11:54:06	2024-10-21 11:54:06
852	PP-Tieuqp9C6o	2.22508706	ITC-YsOvuKKHva	2024-10-21 11:54:06	2024-10-21 11:54:06
853	PP-SLTlZg5BPZ	2.06940094	ITC-stchngBjHP	2024-10-21 11:54:06	2024-10-21 11:54:06
854	PP-aCGxrnlXjm	1.99622929	ITC-wIwSqATAWE	2024-10-21 11:54:06	2024-10-21 11:54:06
855	PP-4qiJYMba23	1.95638222	ITC-ze20g04VB2	2024-10-21 11:54:06	2024-10-21 11:54:06
856	PP-rFir5S1H5t	103.34115941	ITC-QXGzxMvmKL	2024-10-21 11:54:06	2024-10-21 11:54:06
857	PP-HICKLsEgED	2.03606513	ITC-YyyMDt1mKa	2024-10-21 11:54:06	2024-10-21 11:54:06
858	PP-J6yDyLPho0	3.87719838	ITC-UYLbRC56Pw	2024-10-21 11:54:06	2024-10-21 11:54:06
859	PP-b7VFJm0oJ9	4.00292610	ITC-hKEcezNxWC	2024-10-21 11:54:06	2024-10-21 11:54:06
860	PP-rUfkSefoSw	3.18761418	ITC-Dcs5Y9nS5L	2024-10-21 11:54:06	2024-10-21 11:54:06
861	PP-luH14eEnm2	1.88589667	ITC-tZg0t4X5xN	2024-10-21 11:54:06	2024-10-21 11:54:06
862	PP-gcLWCFwbjW	2.34838968	ITC-G5Ad3PJneB	2024-10-21 11:54:06	2024-10-21 11:54:06
863	PP-Mvd66NAo3t	1.85161200	ITC-nhcMdc7OyW	2024-10-21 11:54:06	2024-10-21 11:54:06
864	PP-VCiZTRkSoE	1.85161200	ITC-m7i1VBxXNm	2024-10-21 11:54:06	2024-10-21 11:54:06
865	PP-lIp69q3duV	19.18270032	ITC-y9rHXdOAhj	2024-10-21 11:54:06	2024-10-21 11:54:06
866	PP-SvP54DqAKY	25.99035117	ITC-GvO24AQJZE	2024-10-21 11:54:06	2024-10-21 11:54:06
867	PP-gvxGipa5Ym	27.27744936	ITC-IKyyNjzZE0	2024-10-21 11:54:06	2024-10-21 11:54:06
868	PP-6HNehy4tsP	36.02313394	ITC-GQFm3SnCSL	2024-10-28 10:40:59	2024-10-28 10:40:59
869	PP-EDVApLNjXE	29.16007344	ITC-e8x2GsZeYs	2024-10-28 10:40:59	2024-10-28 10:40:59
870	PP-X9L3F6gnIC	21.86915383	ITC-IzyqsIsBho	2024-10-28 10:40:59	2024-10-28 10:40:59
871	PP-PsdambHq0z	19.14987679	ITC-luUiKG6k2m	2024-10-28 10:40:59	2024-10-28 10:40:59
872	PP-ABap78mCjD	29.15711197	ITC-8zlKdVTEKb	2024-10-28 10:40:59	2024-10-28 10:40:59
873	PP-PgRE5pBl4r	19.22802778	ITC-Z0CMEPUbwC	2024-10-28 10:40:59	2024-10-28 10:40:59
874	PP-4asN0tXfo7	9.54994071	ITC-5Z9isUEMMA	2024-10-28 10:40:59	2024-10-28 10:40:59
875	PP-93Qx4ITzyU	14.81289600	ITC-FX6dgjpbdM	2024-10-28 10:40:59	2024-10-28 10:40:59
876	PP-8aOeSNeCM9	11.94314867	ITC-FIuyoYe0ta	2024-10-28 10:40:59	2024-10-28 10:40:59
877	PP-mPm0rgkVuU	12.96128400	ITC-lALXYPYWQ7	2024-10-28 10:40:59	2024-10-28 10:40:59
878	PP-VqP6Bo4PlB	18.51612000	ITC-dHT7IzMEbg	2024-10-28 10:40:59	2024-10-28 10:40:59
879	PP-D6q0OvcULu	8.03661637	ITC-QRO4BEQ6wZ	2024-10-28 10:40:59	2024-10-28 10:40:59
880	PP-c4SzhAP7Z0	24.49594099	ITC-WHqNMXYAxy	2024-10-28 10:40:59	2024-10-28 10:40:59
881	PP-qC6IHfjmmO	12.45538631	ITC-7ApObtH2pi	2024-10-28 10:40:59	2024-10-28 10:40:59
882	PP-r40zIpu17h	36.82266576	ITC-lWkgBm6M8y	2024-10-28 10:40:59	2024-10-28 10:40:59
883	PP-r9m8bMOva6	293.03220159	ITC-XKDP9jfHOn	2024-10-28 10:40:59	2024-10-28 10:40:59
884	PP-fEC5t0j4An	48.04821538	ITC-D7q0DLwZ4y	2024-10-28 10:40:59	2024-10-28 10:40:59
885	PP-5QkcKxjH7k	5.05490076	ITC-MmPRFIosoQ	2024-10-28 10:40:59	2024-10-28 10:40:59
886	PP-BmWGm51tug	6.42482164	ITC-1h9KUgir50	2024-10-28 10:40:59	2024-10-28 10:40:59
887	PP-U61pWgNgvT	13.94215243	ITC-1DHfDk80aX	2024-10-28 10:40:59	2024-10-28 10:40:59
888	PP-yXUkrileoV	11.82934237	ITC-DlaAptpq2c	2024-10-28 10:40:59	2024-10-28 10:40:59
889	PP-uSqtjX5Wdj	22.60113035	ITC-1e2g490fDv	2024-10-28 10:40:59	2024-10-28 10:40:59
890	PP-zWgfEn0zfq	33.64262659	ITC-xWYNRrs8yJ	2024-10-28 10:40:59	2024-10-28 10:40:59
891	PP-FYUZ9KX67W	5.55483600	ITC-5SKQtT3vyf	2024-10-28 10:40:59	2024-10-28 10:40:59
892	PP-d6MoJFSyUI	24.83873700	ITC-4lqsfdy1G3	2024-10-28 10:40:59	2024-10-28 10:40:59
893	PP-Dj0DB1XCDG	18.53463612	ITC-BKrKKpzteK	2024-10-28 10:40:59	2024-10-28 10:40:59
894	PP-CSFtarim3B	15.74053279	ITC-ncLOsPXpV0	2024-10-28 10:40:59	2024-10-28 10:40:59
895	PP-7Ry38JDlTx	27.09679000	ITC-gngFgoaoGi	2024-10-28 10:40:59	2024-10-28 10:40:59
896	PP-mrkSY2t86Z	27.09679000	ITC-zr840ljEPc	2024-10-28 10:40:59	2024-10-28 10:40:59
897	PP-1kjSf8rsFn	31.49511591	ITC-p9IWpWM5vc	2024-10-28 10:40:59	2024-10-28 10:40:59
898	PP-PhrKwAz4Xd	15.45893004	ITC-flY9k7HKFl	2024-10-28 10:40:59	2024-10-28 10:40:59
899	PP-6B4aZ9ediT	14.48578406	ITC-ft4j6MrmEp	2024-10-28 10:40:59	2024-10-28 10:40:59
900	PP-p8m9uQjlSm	145.04911687	ITC-lcMghoXuxS	2024-10-28 10:40:59	2024-10-28 10:40:59
901	PP-fwshgthLvZ	296.93787812	ITC-NHnbmFh8Ej	2024-10-28 10:40:59	2024-10-28 10:40:59
902	PP-4WpKQG77hX	33.35518029	ITC-ncZ6SSLhOc	2024-10-28 10:40:59	2024-10-28 10:40:59
903	PP-kJQvr68RzI	45.16134000	ITC-3uK8WuMYbf	2024-10-28 10:40:59	2024-10-28 10:40:59
904	PP-91MahZFhOF	15.74801673	ITC-wi4u5b0jdJ	2024-10-28 10:40:59	2024-10-28 10:40:59
905	PP-Xz3ASGMBLf	4.38519372	ITC-0KZCWKi2eL	2024-10-28 10:40:59	2024-10-28 10:40:59
906	PP-CkXBlGM9nJ	28.86041456	ITC-XbiBgsSJkM	2024-10-28 10:40:59	2024-10-28 10:40:59
907	PP-F5OcaW1HdH	2.33772365	ITC-2KcILuMvKT	2024-10-28 10:40:59	2024-10-28 10:40:59
908	PP-hqETF9XksI	12.92516883	ITC-QuCgap9eFI	2024-10-28 10:40:59	2024-10-28 10:40:59
909	PP-wjTm2b22hz	57.74039649	ITC-G5BiAoEKzU	2024-10-28 10:40:59	2024-10-28 10:40:59
910	PP-Om9UieQEWv	30.40749552	ITC-NK4e9qdvdM	2024-10-28 10:40:59	2024-10-28 10:40:59
911	PP-n4RLM3o0ri	7.83975531	ITC-7rUVFjBAdo	2024-10-28 10:40:59	2024-10-28 10:40:59
912	PP-cf3ZJwSQ1s	5.55483600	ITC-wKyyQSfDUp	2024-10-28 10:40:59	2024-10-28 10:40:59
913	PP-oBGEymxyxH	4.62903000	ITC-mFhh9aMM9V	2024-10-28 10:40:59	2024-10-28 10:40:59
914	PP-2gVVtU3BIX	3.10625780	ITC-SNfUA4zPX9	2024-10-28 10:40:59	2024-10-28 10:40:59
915	PP-9brOEnY2P8	2.25806700	ITC-oa4hGuM7UC	2024-10-28 10:40:59	2024-10-28 10:40:59
916	PP-dHGllga6Lf	0.00000000	ITC-JGR3FfLxC6	2024-10-28 10:40:59	2024-10-28 10:40:59
917	PP-Fn9purzCcr	2.26628703	ITC-YsOvuKKHva	2024-10-28 10:40:59	2024-10-28 10:40:59
918	PP-ApyFDdNy81	2.06940094	ITC-stchngBjHP	2024-10-28 10:40:59	2024-10-28 10:40:59
919	PP-nvCjzBzi62	2.03319171	ITC-wIwSqATAWE	2024-10-28 10:40:59	2024-10-28 10:40:59
920	PP-WxdkK2FLQx	1.99260683	ITC-ze20g04VB2	2024-10-28 10:40:59	2024-10-28 10:40:59
921	PP-dCyy48Qwmc	105.67467203	ITC-QXGzxMvmKL	2024-10-28 10:40:59	2024-10-28 10:40:59
922	PP-OKwaYe4QOy	2.11146518	ITC-YyyMDt1mKa	2024-10-28 10:40:59	2024-10-28 10:40:59
923	PP-X8CnssKNSE	3.94898905	ITC-UYLbRC56Pw	2024-10-28 10:40:59	2024-10-28 10:40:59
924	PP-NnC2DpdZVg	4.07704476	ITC-hKEcezNxWC	2024-10-28 10:40:59	2024-10-28 10:40:59
925	PP-42QnUXTVw7	3.25959265	ITC-Dcs5Y9nS5L	2024-10-28 10:40:59	2024-10-28 10:40:59
926	PP-TPSgOl2hnB	1.92081616	ITC-tZg0t4X5xN	2024-10-28 10:40:59	2024-10-28 10:40:59
927	PP-NbCZx8Sn5n	2.34838968	ITC-G5Ad3PJneB	2024-10-28 10:40:59	2024-10-28 10:40:59
928	PP-WQxhoFAySG	1.88589667	ITC-nhcMdc7OyW	2024-10-28 10:40:59	2024-10-28 10:40:59
929	PP-y1Ptu4HcMO	1.88589667	ITC-m7i1VBxXNm	2024-10-28 10:40:59	2024-10-28 10:40:59
930	PP-7Cn7V5kQkC	19.53788950	ITC-y9rHXdOAhj	2024-10-28 10:40:59	2024-10-28 10:40:59
931	PP-p1YXtHrVNj	25.99035117	ITC-GvO24AQJZE	2024-10-28 10:40:59	2024-10-28 10:40:59
932	PP-dvDJYU7Wr8	27.27744936	ITC-IKyyNjzZE0	2024-10-28 10:40:59	2024-10-28 10:40:59
933	PP-PPk9hXRdCn	4.07354640	ITC-f55SXSrqrR	2024-10-28 10:40:59	2024-10-28 10:40:59
934	PP-moJJSQOneO	7.55457696	ITC-Igk7zIiFps	2024-10-28 10:40:59	2024-10-28 10:40:59
935	PP-SGG7k75thS	1.85161200	ITC-pr2IoJIB0z	2024-10-28 10:40:59	2024-10-28 10:40:59
936	PP-jtqefs7frQ	157.16146320	ITC-vAwQY8gH2H	2024-10-28 10:40:59	2024-10-28 10:40:59
937	PP-YzYczdAa5p	36.02313394	ITC-GQFm3SnCSL	2024-11-05 05:27:00	2024-11-05 05:27:00
938	PP-2T0lYTjG1y	29.16007344	ITC-e8x2GsZeYs	2024-11-05 05:27:00	2024-11-05 05:27:00
939	PP-PJn7pToUOQ	22.36297397	ITC-IzyqsIsBho	2024-11-05 05:27:00	2024-11-05 05:27:00
940	PP-RmnVBAjqUJ	19.14987679	ITC-luUiKG6k2m	2024-11-05 05:27:00	2024-11-05 05:27:00
941	PP-U80bdwtP7D	29.15711197	ITC-8zlKdVTEKb	2024-11-05 05:27:00	2024-11-05 05:27:00
942	PP-hYyZxh0Q4R	19.22802778	ITC-Z0CMEPUbwC	2024-11-05 05:27:00	2024-11-05 05:27:00
943	PP-ShOlT37cZJ	9.54994071	ITC-5Z9isUEMMA	2024-11-05 05:27:00	2024-11-05 05:27:00
944	PP-L4XgLBVGAO	14.81289600	ITC-FX6dgjpbdM	2024-11-05 05:27:00	2024-11-05 05:27:00
945	PP-JLgQCyRQkl	11.94314867	ITC-FIuyoYe0ta	2024-11-05 05:27:00	2024-11-05 05:27:00
946	PP-sdQeUbjU01	12.96128400	ITC-lALXYPYWQ7	2024-11-05 05:27:00	2024-11-05 05:27:00
947	PP-yJ5WgvNS30	18.51612000	ITC-dHT7IzMEbg	2024-11-05 05:27:00	2024-11-05 05:27:00
948	PP-uprBiJUh9L	8.03661637	ITC-QRO4BEQ6wZ	2024-11-05 05:27:00	2024-11-05 05:27:00
949	PP-7k8jWV0N7B	25.04907575	ITC-WHqNMXYAxy	2024-11-05 05:27:00	2024-11-05 05:27:00
950	PP-HZ37YeFELA	12.73663728	ITC-7ApObtH2pi	2024-11-05 05:27:00	2024-11-05 05:27:00
951	PP-RIQ3j87Smn	37.65414622	ITC-lWkgBm6M8y	2024-11-05 05:27:00	2024-11-05 05:27:00
952	PP-jSt5pSUBIF	300.31074933	ITC-XKDP9jfHOn	2024-11-05 05:27:00	2024-11-05 05:27:00
953	PP-KZkT6ePIrj	48.93788190	ITC-D7q0DLwZ4y	2024-11-05 05:27:00	2024-11-05 05:27:00
954	PP-Eb8oAPZWwH	5.05490076	ITC-MmPRFIosoQ	2024-11-05 05:27:00	2024-11-05 05:27:00
955	PP-SVpelZJQ1e	6.54378441	ITC-1h9KUgir50	2024-11-05 05:27:00	2024-11-05 05:27:00
956	PP-eETC5XXsU9	13.94215243	ITC-1DHfDk80aX	2024-11-05 05:27:00	2024-11-05 05:27:00
957	PP-xHkyIa3ke8	11.82934237	ITC-DlaAptpq2c	2024-11-05 05:27:00	2024-11-05 05:27:00
958	PP-LVNaoiYp2u	22.60113035	ITC-1e2g490fDv	2024-11-05 05:27:00	2024-11-05 05:27:00
959	PP-4f8vEmKEVY	33.64262659	ITC-xWYNRrs8yJ	2024-11-05 05:27:00	2024-11-05 05:27:00
960	PP-7JoPJGtHzY	5.55483600	ITC-5SKQtT3vyf	2024-11-05 05:27:00	2024-11-05 05:27:00
961	PP-4vEc036DJ6	24.83873700	ITC-4lqsfdy1G3	2024-11-05 05:27:00	2024-11-05 05:27:00
962	PP-kg7nB9pQVQ	18.53463612	ITC-BKrKKpzteK	2024-11-05 05:27:00	2024-11-05 05:27:00
963	PP-Zr0Yi8AMc8	16.09596457	ITC-ncLOsPXpV0	2024-11-05 05:27:00	2024-11-05 05:27:00
964	PP-uYiUh9aj0x	27.09679000	ITC-gngFgoaoGi	2024-11-05 05:27:00	2024-11-05 05:27:00
965	PP-geXPtEaJtp	27.09679000	ITC-zr840ljEPc	2024-11-05 05:27:00	2024-11-05 05:27:00
966	PP-2maCGOgsmT	32.20629673	ITC-p9IWpWM5vc	2024-11-05 05:27:00	2024-11-05 05:27:00
967	PP-xajdgMbvCg	15.80800304	ITC-flY9k7HKFl	2024-11-05 05:27:00	2024-11-05 05:27:00
968	PP-fOiHp9aLr8	14.48578406	ITC-ft4j6MrmEp	2024-11-05 05:27:00	2024-11-05 05:27:00
969	PP-hR1K5dqp7k	145.04911687	ITC-lcMghoXuxS	2024-11-05 05:27:00	2024-11-05 05:27:00
970	PP-QCVvxmyCx4	296.93787812	ITC-NHnbmFh8Ej	2024-11-05 05:27:00	2024-11-05 05:27:00
971	PP-qb4aKTx90a	34.10836261	ITC-ncZ6SSLhOc	2024-11-05 05:27:00	2024-11-05 05:27:00
972	PP-NM3cIek4n9	45.16134000	ITC-3uK8WuMYbf	2024-11-05 05:27:00	2024-11-05 05:27:00
973	PP-vevyNA9PQY	16.10361750	ITC-wi4u5b0jdJ	2024-11-05 05:27:00	2024-11-05 05:27:00
974	PP-dSdEXCHYrT	4.46639050	ITC-0KZCWKi2eL	2024-11-05 05:27:00	2024-11-05 05:27:00
975	PP-rEFlGFCiV4	29.51210205	ITC-XbiBgsSJkM	2024-11-05 05:27:00	2024-11-05 05:27:00
976	PP-rry82jzgs2	2.33772365	ITC-2KcILuMvKT	2024-11-05 05:27:00	2024-11-05 05:27:00
977	PP-ZdQDAiTNdG	12.92516883	ITC-QuCgap9eFI	2024-11-05 05:27:00	2024-11-05 05:27:00
978	PP-9GHaYn9q2a	57.74039649	ITC-G5BiAoEKzU	2024-11-05 05:27:00	2024-11-05 05:27:00
979	PP-TKgR1v18DT	31.09411714	ITC-NK4e9qdvdM	2024-11-05 05:27:00	2024-11-05 05:27:00
980	PP-0qmNFi3CaM	7.83975531	ITC-7rUVFjBAdo	2024-11-05 05:27:00	2024-11-05 05:27:00
981	PP-n3vWqrS3ly	5.55483600	ITC-wKyyQSfDUp	2024-11-05 05:27:00	2024-11-05 05:27:00
982	PP-c4xrnso6Um	4.62903000	ITC-mFhh9aMM9V	2024-11-05 05:27:00	2024-11-05 05:27:00
983	PP-fIkuHNUOIh	3.16377364	ITC-SNfUA4zPX9	2024-11-05 05:27:00	2024-11-05 05:27:00
984	PP-33GWINjMSF	2.25806700	ITC-oa4hGuM7UC	2024-11-05 05:27:00	2024-11-05 05:27:00
985	PP-E5Dv23R6o2	0.00000000	ITC-JGR3FfLxC6	2024-11-05 05:27:00	2024-11-05 05:27:00
986	PP-sTjo9EM06M	2.30824988	ITC-YsOvuKKHva	2024-11-05 05:27:00	2024-11-05 05:27:00
987	PP-1xMdgQ80fZ	2.06940094	ITC-stchngBjHP	2024-11-05 05:27:00	2024-11-05 05:27:00
988	PP-kCSzCarnpI	2.07083853	ITC-wIwSqATAWE	2024-11-05 05:27:00	2024-11-05 05:27:00
989	PP-Bs3qyODJm6	2.02950218	ITC-ze20g04VB2	2024-11-05 05:27:00	2024-11-05 05:27:00
990	PP-v3daQ2TINF	108.06087693	ITC-QXGzxMvmKL	2024-11-05 05:27:00	2024-11-05 05:27:00
991	PP-snSjUUSRxi	2.15056132	ITC-YyyMDt1mKa	2024-11-05 05:27:00	2024-11-05 05:27:00
992	PP-pEzVGfr7Nk	4.02210901	ITC-UYLbRC56Pw	2024-11-05 05:27:00	2024-11-05 05:27:00
993	PP-4gaNjdHPIu	4.15253581	ITC-hKEcezNxWC	2024-11-05 05:27:00	2024-11-05 05:27:00
994	PP-kBi9KIqQBb	3.33319643	ITC-Dcs5Y9nS5L	2024-11-05 05:27:00	2024-11-05 05:27:00
995	PP-8aYPvSZbch	1.95638222	ITC-tZg0t4X5xN	2024-11-05 05:27:00	2024-11-05 05:27:00
996	PP-fmsjWoFEUg	2.34838968	ITC-G5Ad3PJneB	2024-11-05 05:27:00	2024-11-05 05:27:00
997	PP-W0I8lu3cWi	1.92081616	ITC-nhcMdc7OyW	2024-11-05 05:27:00	2024-11-05 05:27:00
998	PP-TqqDzATJ55	1.92081616	ITC-m7i1VBxXNm	2024-11-05 05:27:00	2024-11-05 05:27:00
999	PP-akMpchbct1	19.89965541	ITC-y9rHXdOAhj	2024-11-05 05:27:00	2024-11-05 05:27:00
1000	PP-Oz4SxC1sSO	25.99035117	ITC-GvO24AQJZE	2024-11-05 05:27:00	2024-11-05 05:27:00
1001	PP-s55L3jX6uL	27.27744936	ITC-IKyyNjzZE0	2024-11-05 05:27:00	2024-11-05 05:27:00
1002	PP-FABZGczvsf	4.14897267	ITC-f55SXSrqrR	2024-11-05 05:27:00	2024-11-05 05:27:00
1003	PP-qQSdsNOpDh	7.69445841	ITC-Igk7zIiFps	2024-11-05 05:27:00	2024-11-05 05:27:00
1004	PP-kypClg7I37	1.85161200	ITC-pr2IoJIB0z	2024-11-05 05:27:00	2024-11-05 05:27:00
1005	PP-YaSW2FjCHn	160.71027434	ITC-vAwQY8gH2H	2024-11-05 05:27:00	2024-11-05 05:27:00
1006	PP-xjPcoVP8af	36.99924523	ITC-GQFm3SnCSL	2024-11-11 05:48:07	2024-11-11 05:48:07
1007	PP-xf3g6OkWLW	29.81852744	ITC-e8x2GsZeYs	2024-11-11 05:48:07	2024-11-11 05:48:07
1008	PP-zv2DklF8P4	22.86794491	ITC-IzyqsIsBho	2024-11-11 05:48:07	2024-11-11 05:48:07
1009	PP-tLzM7IYv4s	19.14987679	ITC-luUiKG6k2m	2024-11-11 05:48:07	2024-11-11 05:48:07
1010	PP-97lnwTx5a6	29.15711197	ITC-8zlKdVTEKb	2024-11-11 05:48:07	2024-11-11 05:48:07
1011	PP-gCS88ERtCQ	19.22802778	ITC-Z0CMEPUbwC	2024-11-11 05:48:07	2024-11-11 05:48:07
1012	PP-jdHswPTUTc	9.54994071	ITC-5Z9isUEMMA	2024-11-11 05:48:07	2024-11-11 05:48:07
1013	PP-YwVpK7rXp9	14.81289600	ITC-FX6dgjpbdM	2024-11-11 05:48:07	2024-11-11 05:48:07
1014	PP-E6Ymxcv4eR	11.94314867	ITC-FIuyoYe0ta	2024-11-11 05:48:07	2024-11-11 05:48:07
1015	PP-AJ4tmMrYI1	12.96128400	ITC-lALXYPYWQ7	2024-11-11 05:48:07	2024-11-11 05:48:07
1016	PP-37O36RtNto	18.51612000	ITC-dHT7IzMEbg	2024-11-11 05:48:07	2024-11-11 05:48:07
1017	PP-bpd13zMHMb	8.03661637	ITC-QRO4BEQ6wZ	2024-11-11 05:48:07	2024-11-11 05:48:07
1018	PP-caSViObmhB	25.04907575	ITC-WHqNMXYAxy	2024-11-11 05:48:07	2024-11-11 05:48:07
1019	PP-lpOgs1GOjX	12.73663728	ITC-7ApObtH2pi	2024-11-11 05:48:07	2024-11-11 05:48:07
1020	PP-eva38aaJFt	38.50440207	ITC-lWkgBm6M8y	2024-11-11 05:48:07	2024-11-11 05:48:07
1021	PP-9pGXNA6OhP	307.77008694	ITC-XKDP9jfHOn	2024-11-11 05:48:07	2024-11-11 05:48:07
1022	PP-pdZijmMp9S	49.84402160	ITC-D7q0DLwZ4y	2024-11-11 05:48:07	2024-11-11 05:48:07
1023	PP-EV9rgzRaOj	5.05490076	ITC-MmPRFIosoQ	2024-11-11 05:48:07	2024-11-11 05:48:07
1024	PP-7iZy0bbMZF	6.66494990	ITC-1h9KUgir50	2024-11-11 05:48:07	2024-11-11 05:48:07
1025	PP-mo6hFduEPk	13.94215243	ITC-1DHfDk80aX	2024-11-11 05:48:07	2024-11-11 05:48:07
1026	PP-zL31CfqdEb	11.82934237	ITC-DlaAptpq2c	2024-11-11 05:48:07	2024-11-11 05:48:07
1027	PP-I1w1NpJR82	22.60113035	ITC-1e2g490fDv	2024-11-11 05:48:07	2024-11-11 05:48:07
1028	PP-tlauohahSt	33.64262659	ITC-xWYNRrs8yJ	2024-11-11 05:48:07	2024-11-11 05:48:07
1029	PP-Rh2jBbVemj	5.55483600	ITC-5SKQtT3vyf	2024-11-11 05:48:07	2024-11-11 05:48:07
1030	PP-MadrruUMLv	24.83873700	ITC-4lqsfdy1G3	2024-11-11 05:48:07	2024-11-11 05:48:07
1031	PP-N3E9i5uQz7	18.53463612	ITC-BKrKKpzteK	2024-11-11 05:48:07	2024-11-11 05:48:07
1032	PP-RL8P5BV8ww	16.45942223	ITC-ncLOsPXpV0	2024-11-11 05:48:07	2024-11-11 05:48:07
1033	PP-i1TteCrs6M	27.09679000	ITC-gngFgoaoGi	2024-11-11 05:48:07	2024-11-11 05:48:07
1034	PP-HklfChGMg6	27.09679000	ITC-zr840ljEPc	2024-11-11 05:48:07	2024-11-11 05:48:07
1035	PP-LBKh6u86nc	32.93353649	ITC-p9IWpWM5vc	2024-11-11 05:48:07	2024-11-11 05:48:07
1036	PP-FqbfuS0ZhF	16.16495834	ITC-flY9k7HKFl	2024-11-11 05:48:07	2024-11-11 05:48:07
1037	PP-riLIMUoTvG	14.48578406	ITC-ft4j6MrmEp	2024-11-11 05:48:07	2024-11-11 05:48:07
1038	PP-W6dQhVw9tj	145.04911687	ITC-lcMghoXuxS	2024-11-11 05:48:07	2024-11-11 05:48:07
1039	PP-gXW0IJJNz0	296.93787812	ITC-NHnbmFh8Ej	2024-11-11 05:48:07	2024-11-11 05:48:07
1040	PP-O8t4PLDGpy	34.10836261	ITC-ncZ6SSLhOc	2024-11-11 05:48:07	2024-11-11 05:48:07
1041	PP-mxeYWzhhuJ	45.16134000	ITC-3uK8WuMYbf	2024-11-11 05:48:07	2024-11-11 05:48:07
1042	PP-JSf1v4EfLd	16.46724797	ITC-wi4u5b0jdJ	2024-11-11 05:48:07	2024-11-11 05:48:07
1043	PP-DXiWMVA5jW	4.54909072	ITC-0KZCWKi2eL	2024-11-11 05:48:07	2024-11-11 05:48:07
1044	PP-cpXvb5M6LO	27.25403505	ITC-XbiBgsSJkM	2024-11-11 05:48:07	2024-11-11 05:48:07
1045	PP-HxB2yOeQKO	2.33772365	ITC-2KcILuMvKT	2024-11-11 05:48:07	2024-11-11 05:48:07
1046	PP-InUmVdJf4G	12.92516883	ITC-QuCgap9eFI	2024-11-11 05:48:07	2024-11-11 05:48:07
1047	PP-kqts5DrOFz	52.99845579	ITC-G5BiAoEKzU	2024-11-11 05:48:07	2024-11-11 05:48:07
1048	PP-n5PUxs7yxf	31.09411714	ITC-NK4e9qdvdM	2024-11-11 05:48:07	2024-11-11 05:48:07
1049	PP-csxxonhZFU	7.83975531	ITC-7rUVFjBAdo	2024-11-11 05:48:07	2024-11-11 05:48:07
1050	PP-skdYGgkk8W	1.85161200	ITC-wKyyQSfDUp	2024-11-11 05:48:07	2024-11-11 05:48:07
1051	PP-oesjH2C9Vw	3.70322400	ITC-mFhh9aMM9V	2024-11-11 05:48:07	2024-11-11 05:48:07
1052	PP-mE2w2EZOce	0.38635564	ITC-SNfUA4zPX9	2024-11-11 05:48:07	2024-11-11 05:48:07
1053	PP-pu5nJVEPzB	2.25806700	ITC-oa4hGuM7UC	2024-11-11 05:48:07	2024-11-11 05:48:07
1054	PP-aFSqdRE4dG	0.00000000	ITC-JGR3FfLxC6	2024-11-11 05:48:07	2024-11-11 05:48:07
1055	PP-UOiBrCuFvS	2.35098971	ITC-YsOvuKKHva	2024-11-11 05:48:07	2024-11-11 05:48:07
1056	PP-9csXRObbnX	2.06940094	ITC-stchngBjHP	2024-11-11 05:48:07	2024-11-11 05:48:07
1057	PP-QYQ1aWroY3	2.10918243	ITC-wIwSqATAWE	2024-11-11 05:48:07	2024-11-11 05:48:07
1058	PP-ILVx65jBuq	2.06708068	ITC-ze20g04VB2	2024-11-11 05:48:07	2024-11-11 05:48:07
1059	PP-OBlX01lJlp	110.50096393	ITC-QXGzxMvmKL	2024-11-11 05:48:07	2024-11-11 05:48:07
1060	PP-NpKShEcXUU	2.15056132	ITC-YyyMDt1mKa	2024-11-11 05:48:07	2024-11-11 05:48:07
1061	PP-ikDkWGiZtQ	4.09658286	ITC-UYLbRC56Pw	2024-11-11 05:48:07	2024-11-11 05:48:07
1062	PP-53XHf0xTTA	4.22942466	ITC-hKEcezNxWC	2024-11-11 05:48:07	2024-11-11 05:48:07
1063	PP-QwA3qUDREc	3.40846224	ITC-Dcs5Y9nS5L	2024-11-11 05:48:07	2024-11-11 05:48:07
1064	PP-Hvwf6wKu3w	1.99260683	ITC-tZg0t4X5xN	2024-11-11 05:48:07	2024-11-11 05:48:07
1065	PP-yq2jM99ZSH	2.34838968	ITC-G5Ad3PJneB	2024-11-11 05:48:07	2024-11-11 05:48:07
1066	PP-89iQ64W4cZ	1.95638222	ITC-nhcMdc7OyW	2024-11-11 05:48:07	2024-11-11 05:48:07
1067	PP-BtWIrFRw1H	1.95638222	ITC-m7i1VBxXNm	2024-11-11 05:48:07	2024-11-11 05:48:07
1068	PP-l33YhzSgwu	19.89965541	ITC-y9rHXdOAhj	2024-11-11 05:48:07	2024-11-11 05:48:07
1069	PP-iZbYEZ913Q	25.99035117	ITC-GvO24AQJZE	2024-11-11 05:48:07	2024-11-11 05:48:07
1070	PP-6ipDQjW9dm	27.27744936	ITC-IKyyNjzZE0	2024-11-11 05:48:07	2024-11-11 05:48:07
1071	PP-u4tLBq7zsh	4.22579555	ITC-f55SXSrqrR	2024-11-11 05:48:07	2024-11-11 05:48:07
1072	PP-7oybZJFvdl	7.83692993	ITC-Igk7zIiFps	2024-11-11 05:48:07	2024-11-11 05:48:07
1073	PP-4uSSrDvV5W	1.85161200	ITC-pr2IoJIB0z	2024-11-11 05:48:07	2024-11-11 05:48:07
1074	PP-s26rthQWS9	164.33922001	ITC-vAwQY8gH2H	2024-11-11 05:48:07	2024-11-11 05:48:07
1075	PP-dqaGOUFfIL	1.85161200	ITC-0zjnsbw0wu	2024-11-11 05:48:07	2024-11-11 05:48:07
1076	PP-hUoNsEgkXh	36.99924523	ITC-GQFm3SnCSL	2024-11-18 04:31:28	2024-11-18 04:31:28
1077	PP-tiyRTpNSNB	29.81852744	ITC-e8x2GsZeYs	2024-11-18 04:31:28	2024-11-18 04:31:28
1078	PP-F86amAhnAF	23.38431843	ITC-IzyqsIsBho	2024-11-18 04:31:28	2024-11-18 04:31:28
1079	PP-96SPWl38Dt	19.14987679	ITC-luUiKG6k2m	2024-11-18 04:31:28	2024-11-18 04:31:28
1080	PP-cNycrLHCaM	29.15711197	ITC-8zlKdVTEKb	2024-11-18 04:31:28	2024-11-18 04:31:28
1081	PP-blkjDmt22r	19.22802778	ITC-Z0CMEPUbwC	2024-11-18 04:31:28	2024-11-18 04:31:28
1082	PP-cooWq1bHlN	9.54994071	ITC-5Z9isUEMMA	2024-11-18 04:31:28	2024-11-18 04:31:28
1083	PP-RgMq3XPI2T	14.81289600	ITC-FX6dgjpbdM	2024-11-18 04:31:28	2024-11-18 04:31:28
1084	PP-wYK0qzvWnt	11.94314867	ITC-FIuyoYe0ta	2024-11-18 04:31:28	2024-11-18 04:31:28
1085	PP-fCRJicWoxc	12.96128400	ITC-lALXYPYWQ7	2024-11-18 04:31:28	2024-11-18 04:31:28
1086	PP-ALltaVIgGh	18.51612000	ITC-dHT7IzMEbg	2024-11-18 04:31:28	2024-11-18 04:31:28
1087	PP-dOtP36IfPD	8.03661637	ITC-QRO4BEQ6wZ	2024-11-18 04:31:28	2024-11-18 04:31:28
1088	PP-7HdnzFpY7n	25.61470066	ITC-WHqNMXYAxy	2024-11-18 04:31:28	2024-11-18 04:31:28
1089	PP-oLrHHbcL7Z	13.02423908	ITC-7ApObtH2pi	2024-11-18 04:31:28	2024-11-18 04:31:28
1090	PP-XK7aOJNbZR	39.37385727	ITC-lWkgBm6M8y	2024-11-18 04:31:28	2024-11-18 04:31:28
1091	PP-Y8I3lvIDzm	315.41470504	ITC-XKDP9jfHOn	2024-11-18 04:31:28	2024-11-18 04:31:28
1092	PP-DyuDbfTkBV	50.76693948	ITC-D7q0DLwZ4y	2024-11-18 04:31:28	2024-11-18 04:31:28
1093	PP-5DWyFo9fB0	5.05490076	ITC-MmPRFIosoQ	2024-11-18 04:31:28	2024-11-18 04:31:28
1094	PP-hAXo7IsmFO	6.78835892	ITC-1h9KUgir50	2024-11-18 04:31:28	2024-11-18 04:31:28
1095	PP-h5gIcJ7OZG	13.94215243	ITC-1DHfDk80aX	2024-11-18 04:31:28	2024-11-18 04:31:28
1096	PP-fpYtVNvFPn	11.82934237	ITC-DlaAptpq2c	2024-11-18 04:31:28	2024-11-18 04:31:28
1097	PP-N3sXVkiPsV	22.60113035	ITC-1e2g490fDv	2024-11-18 04:31:28	2024-11-18 04:31:28
1098	PP-6T1YBOLhmr	33.64262659	ITC-xWYNRrs8yJ	2024-11-18 04:31:28	2024-11-18 04:31:28
1099	PP-cNxZUJ0khv	5.55483600	ITC-5SKQtT3vyf	2024-11-18 04:31:28	2024-11-18 04:31:28
1100	PP-zYmPlJSz82	24.83873700	ITC-4lqsfdy1G3	2024-11-18 04:31:28	2024-11-18 04:31:28
1101	PP-WhWdrg5RGk	18.53463612	ITC-BKrKKpzteK	2024-11-18 04:31:28	2024-11-18 04:31:28
1102	PP-h6Ye3oWu79	16.83108702	ITC-ncLOsPXpV0	2024-11-18 04:31:28	2024-11-18 04:31:28
1103	PP-41lCVmwD4Y	27.09679000	ITC-gngFgoaoGi	2024-11-18 04:31:28	2024-11-18 04:31:28
1104	PP-BDuxdBDoFK	27.09679000	ITC-zr840ljEPc	2024-11-18 04:31:28	2024-11-18 04:31:28
1105	PP-gHm4qbrWE0	33.67719781	ITC-p9IWpWM5vc	2024-11-18 04:31:28	2024-11-18 04:31:28
1106	PP-gQLhQI6IoS	16.52997393	ITC-flY9k7HKFl	2024-11-18 04:31:28	2024-11-18 04:31:28
1107	PP-7k9uxWKkWn	14.48578406	ITC-ft4j6MrmEp	2024-11-18 04:31:28	2024-11-18 04:31:28
1108	PP-yrAmoDmeEb	145.04911687	ITC-lcMghoXuxS	2024-11-18 04:31:28	2024-11-18 04:31:28
1109	PP-L6x93bB0oA	296.93787812	ITC-NHnbmFh8Ej	2024-11-18 04:31:28	2024-11-18 04:31:28
1110	PP-4PJmqNYjJo	35.64874197	ITC-ncZ6SSLhOc	2024-11-18 04:31:28	2024-11-18 04:31:28
1111	PP-okBV8YH0zA	45.16134000	ITC-3uK8WuMYbf	2024-11-18 04:31:28	2024-11-18 04:31:28
1112	PP-GQF1F4RWZ3	16.83908946	ITC-wi4u5b0jdJ	2024-11-18 04:31:28	2024-11-18 04:31:28
1113	PP-tBrU5YvbDK	4.63332223	ITC-0KZCWKi2eL	2024-11-18 04:31:28	2024-11-18 04:31:28
1114	PP-KYekj1R86w	27.86944943	ITC-XbiBgsSJkM	2024-11-18 04:31:28	2024-11-18 04:31:28
1115	PP-skR1wFFS6r	2.33772365	ITC-2KcILuMvKT	2024-11-18 04:31:28	2024-11-18 04:31:28
1116	PP-MCUpGP7hjp	12.92516883	ITC-QuCgap9eFI	2024-11-18 04:31:28	2024-11-18 04:31:28
1117	PP-5fnLzsmCg1	52.99845579	ITC-G5BiAoEKzU	2024-11-18 04:31:28	2024-11-18 04:31:28
1118	PP-Jhw0pHVwmp	31.09411714	ITC-NK4e9qdvdM	2024-11-18 04:31:28	2024-11-18 04:31:28
1119	PP-8WTR1AZce3	7.83975531	ITC-7rUVFjBAdo	2024-11-18 04:31:28	2024-11-18 04:31:28
1120	PP-JWF4OjlL6M	1.85161200	ITC-wKyyQSfDUp	2024-11-18 04:31:28	2024-11-18 04:31:28
1121	PP-249AFRE06y	3.70322400	ITC-mFhh9aMM9V	2024-11-18 04:31:28	2024-11-18 04:31:28
1122	PP-qGkneCZD7Q	0.38635564	ITC-SNfUA4zPX9	2024-11-18 04:31:28	2024-11-18 04:31:28
1123	PP-WVZiPebml0	2.25806700	ITC-oa4hGuM7UC	2024-11-18 04:31:28	2024-11-18 04:31:28
1124	PP-NlX4ScN2CX	0.00000000	ITC-JGR3FfLxC6	2024-11-18 04:31:28	2024-11-18 04:31:28
1125	PP-MAsGXwd1vK	2.39452092	ITC-YsOvuKKHva	2024-11-18 04:31:28	2024-11-18 04:31:28
1126	PP-n2w3f07vm3	2.06940094	ITC-stchngBjHP	2024-11-18 04:31:28	2024-11-18 04:31:28
1127	PP-4xk89RmtXx	2.14823630	ITC-wIwSqATAWE	2024-11-18 04:31:28	2024-11-18 04:31:28
1128	PP-BGyseWuD0l	2.10535500	ITC-ze20g04VB2	2024-11-18 04:31:28	2024-11-18 04:31:28
1129	PP-ry5NwmqbQH	112.99614973	ITC-QXGzxMvmKL	2024-11-18 04:31:28	2024-11-18 04:31:28
1130	PP-UvtmCdFzPw	2.23020143	ITC-YyyMDt1mKa	2024-11-18 04:31:28	2024-11-18 04:31:28
1131	PP-Ogzr4dRnz4	4.17243568	ITC-UYLbRC56Pw	2024-11-18 04:31:28	2024-11-18 04:31:28
1132	PP-jpLc654lfo	4.30773719	ITC-hKEcezNxWC	2024-11-18 04:31:28	2024-11-18 04:31:28
1133	PP-RrQ3VDTK6U	3.48542760	ITC-Dcs5Y9nS5L	2024-11-18 04:31:28	2024-11-18 04:31:28
1134	PP-F6CEsrhfJz	2.02950218	ITC-tZg0t4X5xN	2024-11-18 04:31:28	2024-11-18 04:31:28
1135	PP-VpS8E5xqW0	2.34838968	ITC-G5Ad3PJneB	2024-11-18 04:31:28	2024-11-18 04:31:28
1136	PP-C46NkWbWhh	1.99260683	ITC-nhcMdc7OyW	2024-11-18 04:31:28	2024-11-18 04:31:28
1137	PP-vMK4UToyLG	1.99260683	ITC-m7i1VBxXNm	2024-11-18 04:31:28	2024-11-18 04:31:28
1138	PP-lhPuhzL0uE	19.89965541	ITC-y9rHXdOAhj	2024-11-18 04:31:28	2024-11-18 04:31:28
1139	PP-tx5OrG7yyH	25.99035117	ITC-GvO24AQJZE	2024-11-18 04:31:28	2024-11-18 04:31:28
1140	PP-r2LfvsjOIK	27.27744936	ITC-IKyyNjzZE0	2024-11-18 04:31:28	2024-11-18 04:31:28
1141	PP-naxYzjUYoG	4.30404089	ITC-f55SXSrqrR	2024-11-18 04:31:28	2024-11-18 04:31:28
1142	PP-yslNThyX49	7.98203946	ITC-Igk7zIiFps	2024-11-18 04:31:28	2024-11-18 04:31:28
1143	PP-RYvXXaNaP8	1.85161200	ITC-pr2IoJIB0z	2024-11-18 04:31:28	2024-11-18 04:31:28
1144	PP-ToXK6yiQBG	168.05010970	ITC-vAwQY8gH2H	2024-11-18 04:31:28	2024-11-18 04:31:28
1145	PP-e1DI62s6Y0	1.88589667	ITC-0zjnsbw0wu	2024-11-18 04:31:28	2024-11-18 04:31:28
1146	PP-RfyukwPRNa	25.44841509	ITC-MO95F6ARHa	2024-11-18 04:31:28	2024-11-18 04:31:28
1147	PP-UptDRELL4H	25.44841509	ITC-AySSuDO0gi	2024-11-18 04:31:28	2024-11-18 04:31:28
1148	PP-1AOvn5lXWS	25.96777050	ITC-lykjDQVfnF	2024-11-18 04:31:28	2024-11-18 04:31:28
1149	PP-2J3VrSsJ67	51.41618559	ITC-J7EPLe7ruL	2024-11-18 04:31:28	2024-11-18 04:31:28
1150	PP-J7lglZutRK	1.85161200	ITC-dI9XrtHs6d	2024-11-18 04:31:28	2024-11-18 04:31:28
1151	PP-yP3JhePPZS	1.85161200	ITC-3Rh4ox62hn	2024-11-18 04:31:28	2024-11-18 04:31:28
1152	PP-pqYf1FHBd5	38.00180601	ITC-GQFm3SnCSL	2024-11-25 09:18:40	2024-11-25 09:18:40
1153	PP-ib40tFzkFE	30.49184977	ITC-e8x2GsZeYs	2024-11-25 09:18:40	2024-11-25 09:18:40
1154	PP-cbSCX5yIer	23.91235200	ITC-IzyqsIsBho	2024-11-25 09:18:40	2024-11-25 09:18:40
1155	PP-H1ZALISl85	19.14987679	ITC-luUiKG6k2m	2024-11-25 09:18:40	2024-11-25 09:18:40
1156	PP-qqjO8DiBa9	29.15711197	ITC-8zlKdVTEKb	2024-11-25 09:18:40	2024-11-25 09:18:40
1157	PP-sfRMSK4r5b	19.22802778	ITC-Z0CMEPUbwC	2024-11-25 09:18:40	2024-11-25 09:18:40
1158	PP-yF2rAjHrxq	9.54994071	ITC-5Z9isUEMMA	2024-11-25 09:18:40	2024-11-25 09:18:40
1159	PP-cade13faaA	14.81289600	ITC-FX6dgjpbdM	2024-11-25 09:18:40	2024-11-25 09:18:40
1160	PP-A4yMn022k8	11.94314867	ITC-FIuyoYe0ta	2024-11-25 09:18:40	2024-11-25 09:18:40
1161	PP-rvpSU4GBT2	12.96128400	ITC-lALXYPYWQ7	2024-11-25 09:18:40	2024-11-25 09:18:40
1162	PP-OooqWeFKfN	18.51612000	ITC-dHT7IzMEbg	2024-11-25 09:18:40	2024-11-25 09:18:40
1163	PP-oHW4CLWUew	8.03661637	ITC-QRO4BEQ6wZ	2024-11-25 09:18:40	2024-11-25 09:18:40
1164	PP-vahTdnIQ1B	25.61470066	ITC-WHqNMXYAxy	2024-11-25 09:18:40	2024-11-25 09:18:40
1165	PP-X4og5sHoXL	13.02423908	ITC-7ApObtH2pi	2024-11-25 09:18:40	2024-11-25 09:18:40
1166	PP-xKmkwqzZ49	39.37385727	ITC-lWkgBm6M8y	2024-11-25 09:18:40	2024-11-25 09:18:40
1167	PP-B4kQXBTotR	323.24920573	ITC-XKDP9jfHOn	2024-11-25 09:18:40	2024-11-25 09:18:40
1168	PP-thkOD24v8p	51.70694623	ITC-D7q0DLwZ4y	2024-11-25 09:18:40	2024-11-25 09:18:40
1169	PP-Xmo2iSOYfS	5.05490076	ITC-MmPRFIosoQ	2024-11-25 09:18:40	2024-11-25 09:18:40
1170	PP-YLynYHNoEm	6.91405298	ITC-1h9KUgir50	2024-11-25 09:18:40	2024-11-25 09:18:40
1171	PP-o1CPcV7YyQ	13.94215243	ITC-1DHfDk80aX	2024-11-25 09:18:40	2024-11-25 09:18:40
1172	PP-pq5HqJWis9	11.82934237	ITC-DlaAptpq2c	2024-11-25 09:18:40	2024-11-25 09:18:40
1173	PP-ID7o9h8Ow1	22.60113035	ITC-1e2g490fDv	2024-11-25 09:18:40	2024-11-25 09:18:40
1174	PP-66yKh6wp3K	33.64262659	ITC-xWYNRrs8yJ	2024-11-25 09:18:40	2024-11-25 09:18:40
1175	PP-s6i09QBPkI	5.55483600	ITC-5SKQtT3vyf	2024-11-25 09:18:40	2024-11-25 09:18:40
1176	PP-hGiH8FZGKt	24.83873700	ITC-4lqsfdy1G3	2024-11-25 09:18:40	2024-11-25 09:18:40
1177	PP-eeG99XttDZ	18.53463612	ITC-BKrKKpzteK	2024-11-25 09:18:40	2024-11-25 09:18:40
1178	PP-nH0EoMFusb	17.21114424	ITC-ncLOsPXpV0	2024-11-25 09:18:40	2024-11-25 09:18:40
1179	PP-rTzqGUTPZP	27.09679000	ITC-gngFgoaoGi	2024-11-25 09:18:40	2024-11-25 09:18:40
1180	PP-xUNoQFhcIF	27.09679000	ITC-zr840ljEPc	2024-11-25 09:18:40	2024-11-25 09:18:40
1181	PP-7r77GaPe6s	34.43765150	ITC-p9IWpWM5vc	2024-11-25 09:18:40	2024-11-25 09:18:40
1182	PP-bLdPQs35l9	16.90323182	ITC-flY9k7HKFl	2024-11-25 09:18:40	2024-11-25 09:18:40
1183	PP-6nikkedqpc	14.48578406	ITC-ft4j6MrmEp	2024-11-25 09:18:40	2024-11-25 09:18:40
1184	PP-oxclXrSMut	145.04911687	ITC-lcMghoXuxS	2024-11-25 09:18:40	2024-11-25 09:18:40
1185	PP-yN5tu1ab5K	296.93787812	ITC-NHnbmFh8Ej	2024-11-25 09:18:40	2024-11-25 09:18:40
1186	PP-oV3Ki5lTvz	36.45371445	ITC-ncZ6SSLhOc	2024-11-25 09:18:40	2024-11-25 09:18:40
1187	PP-NCLg8XzFp4	45.16134000	ITC-3uK8WuMYbf	2024-11-25 09:18:40	2024-11-25 09:18:40
1188	PP-eLBbCTpwbr	17.21932738	ITC-wi4u5b0jdJ	2024-11-25 09:18:40	2024-11-25 09:18:40
1189	PP-7XDkqIKoCO	4.71911338	ITC-0KZCWKi2eL	2024-11-25 09:18:40	2024-11-25 09:18:40
1190	PP-MW6kRz3fkB	27.86944943	ITC-XbiBgsSJkM	2024-11-25 09:18:40	2024-11-25 09:18:40
1191	PP-LNGbsuJDeU	2.33772365	ITC-2KcILuMvKT	2024-11-25 09:18:40	2024-11-25 09:18:40
1192	PP-j1DTPyd0gZ	12.92516883	ITC-QuCgap9eFI	2024-11-25 09:18:40	2024-11-25 09:18:40
1193	PP-ZwUy8peIje	52.99845579	ITC-G5BiAoEKzU	2024-11-25 09:18:40	2024-11-25 09:18:40
1194	PP-TnEgoA46xK	31.09411714	ITC-NK4e9qdvdM	2024-11-25 09:18:40	2024-11-25 09:18:40
1195	PP-dVA4jP7oug	7.83975531	ITC-7rUVFjBAdo	2024-11-25 09:18:40	2024-11-25 09:18:40
1196	PP-42cGYX0CPF	1.85161200	ITC-wKyyQSfDUp	2024-11-25 09:18:40	2024-11-25 09:18:40
1197	PP-Lb5sZ70vuO	3.70322400	ITC-mFhh9aMM9V	2024-11-25 09:18:40	2024-11-25 09:18:40
1198	PP-f31fuUty3F	0.38635564	ITC-SNfUA4zPX9	2024-11-25 09:18:40	2024-11-25 09:18:40
1199	PP-S8WDVzNFIo	2.25806700	ITC-oa4hGuM7UC	2024-11-25 09:18:40	2024-11-25 09:18:40
1200	PP-CyDPa3dgf6	0.00000000	ITC-JGR3FfLxC6	2024-11-25 09:18:40	2024-11-25 09:18:40
1201	PP-TE89dSovDz	2.43885815	ITC-YsOvuKKHva	2024-11-25 09:18:40	2024-11-25 09:18:40
1202	PP-py4Vnuum2N	2.06940094	ITC-stchngBjHP	2024-11-25 09:18:40	2024-11-25 09:18:40
1203	PP-QTZ0NyEB7V	2.18801330	ITC-wIwSqATAWE	2024-11-25 09:18:40	2024-11-25 09:18:40
1204	PP-CJ61iZNAQr	3.99595000	ITC-ze20g04VB2	2024-11-25 09:18:40	2024-11-25 09:18:40
1205	PP-FBjwQo3BYr	112.99614973	ITC-QXGzxMvmKL	2024-11-25 09:18:40	2024-11-25 09:18:40
1206	PP-4FHpJk4s12	2.27149610	ITC-YyyMDt1mKa	2024-11-25 09:18:40	2024-11-25 09:18:40
1207	PP-Y17WW1UsCL	4.24969300	ITC-UYLbRC56Pw	2024-11-25 09:18:40	2024-11-25 09:18:40
1208	PP-H5zM3ULQel	4.30773719	ITC-hKEcezNxWC	2024-11-25 09:18:40	2024-11-25 09:18:40
1209	PP-0phB6spxF6	3.56413089	ITC-Dcs5Y9nS5L	2024-11-25 09:18:40	2024-11-25 09:18:40
1210	PP-j8qMr0isHs	2.06708068	ITC-tZg0t4X5xN	2024-11-25 09:18:40	2024-11-25 09:18:40
1211	PP-jS1xCRPbl8	2.34838968	ITC-G5Ad3PJneB	2024-11-25 09:18:40	2024-11-25 09:18:40
1212	PP-jLqNpjbdh3	2.02950218	ITC-nhcMdc7OyW	2024-11-25 09:18:40	2024-11-25 09:18:40
1213	PP-e7GmFGUlXg	2.02950218	ITC-m7i1VBxXNm	2024-11-25 09:18:40	2024-11-25 09:18:40
1214	PP-Td38R9okRN	19.89965541	ITC-y9rHXdOAhj	2024-11-25 09:18:40	2024-11-25 09:18:40
1215	PP-HuuRqLlm0i	25.99035117	ITC-GvO24AQJZE	2024-11-25 09:18:40	2024-11-25 09:18:40
1216	PP-yoAyGSkSN1	27.27744936	ITC-IKyyNjzZE0	2024-11-25 09:18:40	2024-11-25 09:18:40
1217	PP-cUJ9RnTt3r	4.38373502	ITC-f55SXSrqrR	2024-11-25 09:18:40	2024-11-25 09:18:40
1218	PP-YcZfOzTNgw	9.91444972	ITC-Igk7zIiFps	2024-11-25 09:18:40	2024-11-25 09:18:40
1219	PP-wkabRM8ubr	1.85161200	ITC-pr2IoJIB0z	2024-11-25 09:18:40	2024-11-25 09:18:40
1220	PP-nw1nNSvuKK	171.84479377	ITC-vAwQY8gH2H	2024-11-25 09:18:40	2024-11-25 09:18:40
1221	PP-aDnopZQSVw	1.92081616	ITC-0zjnsbw0wu	2024-11-25 09:18:40	2024-11-25 09:18:40
1222	PP-Dh5QHsuvrs	26.02305735	ITC-MO95F6ARHa	2024-11-25 09:18:40	2024-11-25 09:18:40
1223	PP-kIO3CgDKZI	26.02305735	ITC-AySSuDO0gi	2024-11-25 09:18:40	2024-11-25 09:18:40
1224	PP-87KYAVg1sP	25.96777050	ITC-lykjDQVfnF	2024-11-25 09:18:40	2024-11-25 09:18:40
1225	PP-ZwqZr4WtNg	51.41618559	ITC-J7EPLe7ruL	2024-11-25 09:18:40	2024-11-25 09:18:40
1226	PP-LSZdbJTp0v	3.70322400	ITC-dI9XrtHs6d	2024-11-25 09:18:40	2024-11-25 09:18:40
1227	PP-CMtCXiXhKv	1.85161200	ITC-3Rh4ox62hn	2024-11-25 09:18:40	2024-11-25 09:18:40
1228	PP-oDuzWGrR9W	9.07289880	ITC-lvrnrUsdGJ	2024-11-25 09:18:40	2024-11-25 09:18:40
1229	PP-7v8DW7N515	18.14579760	ITC-FjvE5ODTlu	2024-11-25 09:18:40	2024-11-25 09:18:40
1230	PP-uuV62l0wLU	21.72260454	ITC-N6BOPMHJ5z	2024-11-25 09:18:40	2024-11-25 09:18:40
1231	PP-qiEfaNW1JB	39.14186019	ITC-GQFm3SnCSL	2024-12-02 07:54:48	2024-12-02 07:54:48
1232	PP-Fqy9F1FxQL	31.40660526	ITC-e8x2GsZeYs	2024-12-02 07:54:48	2024-12-02 07:54:48
1233	PP-zC8Caxa6Tz	25.18587820	ITC-IzyqsIsBho	2024-12-02 07:54:48	2024-12-02 07:54:48
1234	PP-kAyY6wbzUk	19.72437310	ITC-luUiKG6k2m	2024-12-02 07:54:48	2024-12-02 07:54:48
1235	PP-7zk8AWUcQx	30.03182533	ITC-8zlKdVTEKb	2024-12-02 07:54:48	2024-12-02 07:54:48
1236	PP-BYP8yGjYvD	19.80486862	ITC-Z0CMEPUbwC	2024-12-02 07:54:48	2024-12-02 07:54:48
1237	PP-5stv587bZL	9.83643893	ITC-5Z9isUEMMA	2024-12-02 07:54:48	2024-12-02 07:54:48
1238	PP-dlk4ALLpv4	15.25728288	ITC-FX6dgjpbdM	2024-12-02 07:54:48	2024-12-02 07:54:48
1239	PP-1kxkABdSSk	12.30144313	ITC-FIuyoYe0ta	2024-12-02 07:54:48	2024-12-02 07:54:48
1240	PP-uq5QYJjPB6	13.35012252	ITC-lALXYPYWQ7	2024-12-02 07:54:48	2024-12-02 07:54:48
1241	PP-3f7QI2r9Gh	19.07160360	ITC-dHT7IzMEbg	2024-12-02 07:54:48	2024-12-02 07:54:48
1242	PP-PkQtgDPZNZ	8.27771486	ITC-QRO4BEQ6wZ	2024-12-02 07:54:48	2024-12-02 07:54:48
1243	PP-2FfUvlefBt	26.97889069	ITC-WHqNMXYAxy	2024-12-02 07:54:48	2024-12-02 07:54:48
1244	PP-NxLDgiZfyZ	13.71788518	ITC-7ApObtH2pi	2024-12-02 07:54:48	2024-12-02 07:54:48
1245	PP-QIQvY8tplz	40.55507299	ITC-lWkgBm6M8y	2024-12-02 07:54:48	2024-12-02 07:54:48
1246	PP-FONkggHpYv	332.94668191	ITC-XKDP9jfHOn	2024-12-02 07:54:48	2024-12-02 07:54:48
1247	PP-3aCmQjQfgv	54.24428900	ITC-D7q0DLwZ4y	2024-12-02 07:54:48	2024-12-02 07:54:48
1248	PP-Sni4WbLtXz	5.20654778	ITC-MmPRFIosoQ	2024-12-02 07:54:48	2024-12-02 07:54:48
1249	PP-9DR8PkO2Xz	7.25333665	ITC-1h9KUgir50	2024-12-02 07:54:48	2024-12-02 07:54:48
1250	PP-UmmWedb2WW	14.36041701	ITC-1DHfDk80aX	2024-12-02 07:54:48	2024-12-02 07:54:48
1251	PP-898Cu6xfVr	12.18422264	ITC-DlaAptpq2c	2024-12-02 07:54:48	2024-12-02 07:54:48
1252	PP-rJcEQ35Otn	23.27916426	ITC-1e2g490fDv	2024-12-02 07:54:48	2024-12-02 07:54:48
1253	PP-FENsFsufza	34.65190539	ITC-xWYNRrs8yJ	2024-12-02 07:54:48	2024-12-02 07:54:48
1254	PP-ppWEsV5sMR	5.72148108	ITC-5SKQtT3vyf	2024-12-02 07:54:48	2024-12-02 07:54:48
1255	PP-Fn7b6489Co	25.58389911	ITC-4lqsfdy1G3	2024-12-02 07:54:48	2024-12-02 07:54:48
1256	PP-WKee7emqsd	19.09067520	ITC-BKrKKpzteK	2024-12-02 07:54:48	2024-12-02 07:54:48
1257	PP-DL0VVagUbN	18.12777691	ITC-ncLOsPXpV0	2024-12-02 07:54:48	2024-12-02 07:54:48
1258	PP-uv83V55Pm0	27.90969370	ITC-gngFgoaoGi	2024-12-02 07:54:48	2024-12-02 07:54:48
1259	PP-IlhHOMjQaz	27.90969370	ITC-zr840ljEPc	2024-12-02 07:54:48	2024-12-02 07:54:48
1260	PP-UDiR7NVYFq	36.27173504	ITC-p9IWpWM5vc	2024-12-02 07:54:48	2024-12-02 07:54:48
1261	PP-zhGlC4iliI	17.80346566	ITC-flY9k7HKFl	2024-12-02 07:54:48	2024-12-02 07:54:48
1262	PP-ogfUrXUT0c	14.92035758	ITC-ft4j6MrmEp	2024-12-02 07:54:48	2024-12-02 07:54:48
1263	PP-t7QQYGMqez	149.40059038	ITC-lcMghoXuxS	2024-12-02 07:54:48	2024-12-02 07:54:48
1264	PP-fBLpGfgNNt	305.84601446	ITC-NHnbmFh8Ej	2024-12-02 07:54:48	2024-12-02 07:54:48
1265	PP-H1okgA82Tu	38.39516966	ITC-ncZ6SSLhOc	2024-12-02 07:54:48	2024-12-02 07:54:48
1266	PP-BPCxjX5SFD	46.51618020	ITC-3uK8WuMYbf	2024-12-02 07:54:48	2024-12-02 07:54:48
1267	PP-aW9lZ4G0Hv	18.13639587	ITC-wi4u5b0jdJ	2024-12-02 07:54:48	2024-12-02 07:54:48
1268	PP-FzUc6vniso	4.86068678	ITC-0KZCWKi2eL	2024-12-02 07:54:48	2024-12-02 07:54:48
1269	PP-ecuDhq8Vz5	29.35372307	ITC-XbiBgsSJkM	2024-12-02 07:54:48	2024-12-02 07:54:48
1270	PP-dT3RnpMzcm	2.40785536	ITC-2KcILuMvKT	2024-12-02 07:54:48	2024-12-02 07:54:48
1271	PP-g7QiPRPaVv	13.31292389	ITC-QuCgap9eFI	2024-12-02 07:54:48	2024-12-02 07:54:48
1272	PP-Gg1giKhiVe	54.58840946	ITC-G5BiAoEKzU	2024-12-02 07:54:48	2024-12-02 07:54:48
1273	PP-m4jTXnd7iz	32.02694065	ITC-NK4e9qdvdM	2024-12-02 07:54:48	2024-12-02 07:54:48
1274	PP-5lV900uCWB	8.07494797	ITC-7rUVFjBAdo	2024-12-02 07:54:48	2024-12-02 07:54:48
1275	PP-UCNknFeSah	1.90716036	ITC-wKyyQSfDUp	2024-12-02 07:54:48	2024-12-02 07:54:48
1276	PP-O9jvwDQ9Um	1.90716036	ITC-mFhh9aMM9V	2024-12-02 07:54:48	2024-12-02 07:54:48
1277	PP-ucBGwonJDh	0.39794631	ITC-SNfUA4zPX9	2024-12-02 07:54:48	2024-12-02 07:54:48
1278	PP-Rt3d7VfWfQ	2.32580901	ITC-oa4hGuM7UC	2024-12-02 07:54:48	2024-12-02 07:54:48
1279	PP-ruckumqxiW	0.00000000	ITC-JGR3FfLxC6	2024-12-02 07:54:48	2024-12-02 07:54:48
1280	PP-oogw1Kobvc	2.55853683	ITC-YsOvuKKHva	2024-12-02 07:54:48	2024-12-02 07:54:48
1281	PP-xnmQZ11ZRL	2.13148297	ITC-stchngBjHP	2024-12-02 07:54:48	2024-12-02 07:54:48
1282	PP-NNNd3eEfP9	0.38822227	ITC-wIwSqATAWE	2024-12-02 07:54:48	2024-12-02 07:54:48
1283	PP-L6PGcZwUd3	2.20866814	ITC-ze20g04VB2	2024-12-02 07:54:48	2024-12-02 07:54:48
1284	PP-rpDGOnz3qQ	119.01410886	ITC-QXGzxMvmKL	2024-12-02 07:54:48	2024-12-02 07:54:48
1285	PP-hUnH82sIYo	2.38296206	ITC-YyyMDt1mKa	2024-12-02 07:54:48	2024-12-02 07:54:48
1286	PP-WD5xj7mT3x	4.45823225	ITC-UYLbRC56Pw	2024-12-02 07:54:48	2024-12-02 07:54:48
1287	PP-7f16ILxl3K	4.43696931	ITC-hKEcezNxWC	2024-12-02 07:54:48	2024-12-02 07:54:48
1288	PP-IJsVSjknA5	3.75394970	ITC-Dcs5Y9nS5L	2024-12-02 07:54:48	2024-12-02 07:54:48
1289	PP-MZ3o64Upn7	2.16851565	ITC-tZg0t4X5xN	2024-12-02 07:54:48	2024-12-02 07:54:48
1290	PP-Elj1eiTB4Y	2.41884137	ITC-G5Ad3PJneB	2024-12-02 07:54:48	2024-12-02 07:54:48
1291	PP-9bojLX7kvQ	2.12909310	ITC-nhcMdc7OyW	2024-12-02 07:54:48	2024-12-02 07:54:48
1292	PP-HOS3ZAfsOD	2.12909310	ITC-m7i1VBxXNm	2024-12-02 07:54:48	2024-12-02 07:54:48
1293	PP-xorT0rP5hh	20.49664507	ITC-y9rHXdOAhj	2024-12-02 07:54:48	2024-12-02 07:54:48
1294	PP-a7bsp4Yrpu	26.77006171	ITC-GvO24AQJZE	2024-12-02 07:54:48	2024-12-02 07:54:48
1295	PP-F1IxmkUQQI	28.09577284	ITC-IKyyNjzZE0	2024-12-02 07:54:48	2024-12-02 07:54:48
1296	PP-Q2Toa2xGiH	4.59885193	ITC-f55SXSrqrR	2024-12-02 07:54:48	2024-12-02 07:54:48
1297	PP-Ev4BhqFvE2	10.44247438	ITC-Igk7zIiFps	2024-12-02 07:54:48	2024-12-02 07:54:48
1298	PP-fPSHSlcMTz	1.90716036	ITC-pr2IoJIB0z	2024-12-02 07:54:48	2024-12-02 07:54:48
1299	PP-pg3cihOBOE	180.99691928	ITC-vAwQY8gH2H	2024-12-02 07:54:48	2024-12-02 07:54:48
1300	PP-Dyrf4FMTKa	2.01507369	ITC-0zjnsbw0wu	2024-12-02 07:54:48	2024-12-02 07:54:48
1301	PP-MeNSpGnz8G	27.40899569	ITC-MO95F6ARHa	2024-12-02 07:54:48	2024-12-02 07:54:48
1302	PP-fKloNkQJPM	27.40899569	ITC-AySSuDO0gi	2024-12-02 07:54:48	2024-12-02 07:54:48
1303	PP-NukN6TcGnO	26.74680362	ITC-lykjDQVfnF	2024-12-02 07:54:48	2024-12-02 07:54:48
1304	PP-mn7CqccOFa	52.95867116	ITC-J7EPLe7ruL	2024-12-02 07:54:48	2024-12-02 07:54:48
1305	PP-YBh94KHhzs	3.81432072	ITC-dI9XrtHs6d	2024-12-02 07:54:48	2024-12-02 07:54:48
1306	PP-I6PXyAa5Eq	1.90716036	ITC-3Rh4ox62hn	2024-12-02 07:54:48	2024-12-02 07:54:48
1307	PP-u9haO2QuUU	9.51812049	ITC-lvrnrUsdGJ	2024-12-02 07:54:48	2024-12-02 07:54:48
1308	PP-LEkQGm2Cr2	67.98678834	ITC-FjvE5ODTlu	2024-12-02 07:54:48	2024-12-02 07:54:48
1309	PP-IMHLEQszNr	22.37428268	ITC-N6BOPMHJ5z	2024-12-02 07:54:48	2024-12-02 07:54:48
1310	PP-6eKrmarpNP	1.90716036	ITC-oMN46k7kan	2024-12-02 07:54:48	2024-12-02 07:54:48
1311	PP-AdG2G1ntRR	1.90716036	ITC-m3MRU2bJ9F	2024-12-02 07:54:48	2024-12-02 07:54:48
1312	PP-AlPES7FkQf	1.90716036	ITC-Z692r80YQL	2024-12-02 07:54:48	2024-12-02 07:54:48
1313	PP-pQoofuG6qu	1.90716036	ITC-GgvO41uKHy	2024-12-02 07:54:48	2024-12-02 07:54:48
1314	PP-vTXSnFSDhL	1.90716036	ITC-xNQZsYmvwv	2024-12-02 07:54:48	2024-12-02 07:54:48
1315	PP-Eb4orCQsDR	39.06242478	ITC-GQFm3SnCSL	2024-12-09 12:23:51	2024-12-09 12:23:51
1316	PP-ljRp3drz2G	31.20103195	ITC-e8x2GsZeYs	2024-12-09 12:23:51	2024-12-09 12:23:51
1317	PP-0UQLngVfbv	25.02102294	ITC-IzyqsIsBho	2024-12-09 12:23:51	2024-12-09 12:23:51
1318	PP-9WCmr1Nwfs	19.14987679	ITC-luUiKG6k2m	2024-12-09 12:23:51	2024-12-09 12:23:51
1319	PP-W1NXkaKXtW	29.15711197	ITC-8zlKdVTEKb	2024-12-09 12:23:51	2024-12-09 12:23:51
1320	PP-W8mwdYB4rD	19.22802778	ITC-Z0CMEPUbwC	2024-12-09 12:23:51	2024-12-09 12:23:51
1321	PP-C2oBIJLqbo	9.54994071	ITC-5Z9isUEMMA	2024-12-09 12:23:51	2024-12-09 12:23:51
1322	PP-wpcBPYIG2F	14.81289600	ITC-FX6dgjpbdM	2024-12-09 12:23:51	2024-12-09 12:23:51
1323	PP-3w07CKFtgJ	11.94314867	ITC-FIuyoYe0ta	2024-12-09 12:23:51	2024-12-09 12:23:51
1324	PP-7XMQsKPWNc	12.96128400	ITC-lALXYPYWQ7	2024-12-09 12:23:51	2024-12-09 12:23:51
1325	PP-jkmc1vc74J	18.51612000	ITC-dHT7IzMEbg	2024-12-09 12:23:51	2024-12-09 12:23:51
1326	PP-uQRxbBYFFX	8.03661637	ITC-QRO4BEQ6wZ	2024-12-09 12:23:51	2024-12-09 12:23:51
1327	PP-XUnaNRcUVS	26.19309776	ITC-WHqNMXYAxy	2024-12-09 12:23:51	2024-12-09 12:23:51
1328	PP-8u7dFkO41A	13.31833512	ITC-7ApObtH2pi	2024-12-09 12:23:51	2024-12-09 12:23:51
1329	PP-k8jYaIVixO	40.28961799	ITC-lWkgBm6M8y	2024-12-09 12:23:51	2024-12-09 12:23:51
1330	PP-IXRfzoVUZ1	331.51917847	ITC-XKDP9jfHOn	2024-12-09 12:23:51	2024-12-09 12:23:51
1331	PP-P9MrrALaIZ	53.66875201	ITC-D7q0DLwZ4y	2024-12-09 12:23:51	2024-12-09 12:23:51
1332	PP-SSw8JUPHqG	5.05490076	ITC-MmPRFIosoQ	2024-12-09 12:23:51	2024-12-09 12:23:51
1333	PP-LlYXzZIal3	7.17637807	ITC-1h9KUgir50	2024-12-09 12:23:51	2024-12-09 12:23:51
1334	PP-NYV6OA9QgM	13.94215243	ITC-1DHfDk80aX	2024-12-09 12:23:51	2024-12-09 12:23:51
1335	PP-1YBCf9zkPe	11.82934237	ITC-DlaAptpq2c	2024-12-09 12:23:51	2024-12-09 12:23:51
1336	PP-bT4BZlHIur	22.60113035	ITC-1e2g490fDv	2024-12-09 12:23:51	2024-12-09 12:23:51
1337	PP-nBVRzidLny	33.64262659	ITC-xWYNRrs8yJ	2024-12-09 12:23:51	2024-12-09 12:23:51
1338	PP-oB9lEAQYw3	7.24800889	ITC-5SKQtT3vyf	2024-12-09 12:23:51	2024-12-09 12:23:51
1339	PP-1W7FWq7suG	24.83873700	ITC-4lqsfdy1G3	2024-12-09 12:23:51	2024-12-09 12:23:51
1340	PP-j8pV6rsRcd	18.53463612	ITC-BKrKKpzteK	2024-12-09 12:23:51	2024-12-09 12:23:51
1341	PP-I3Y0ZfiRDk	17.59978341	ITC-ncLOsPXpV0	2024-12-09 12:23:51	2024-12-09 12:23:51
1342	PP-tXKcCeV48p	27.09679000	ITC-gngFgoaoGi	2024-12-09 12:23:51	2024-12-09 12:23:51
1343	PP-4mgheBQoQv	27.09679000	ITC-zr840ljEPc	2024-12-09 12:23:51	2024-12-09 12:23:51
1344	PP-JuWcrYQ0VY	36.03431682	ITC-p9IWpWM5vc	2024-12-09 12:23:51	2024-12-09 12:23:51
1345	PP-F6O6aGHFvz	17.68693230	ITC-flY9k7HKFl	2024-12-09 12:23:51	2024-12-09 12:23:51
1346	PP-frWwlVFDHd	14.48578406	ITC-ft4j6MrmEp	2024-12-09 12:23:51	2024-12-09 12:23:51
1347	PP-FZ38rGLW4N	145.04911687	ITC-lcMghoXuxS	2024-12-09 12:23:51	2024-12-09 12:23:51
1348	PP-GADEQTkawI	296.93787812	ITC-NHnbmFh8Ej	2024-12-09 12:23:51	2024-12-09 12:23:51
1349	PP-Bgh8FiGgut	38.14385240	ITC-ncZ6SSLhOc	2024-12-09 12:23:51	2024-12-09 12:23:51
1350	PP-kfJ5R1dMW7	45.16134000	ITC-3uK8WuMYbf	2024-12-09 12:23:51	2024-12-09 12:23:51
1351	PP-PDty0hEngn	18.01768330	ITC-wi4u5b0jdJ	2024-12-09 12:23:51	2024-12-09 12:23:51
1352	PP-WktRtsPmz4	4.71911338	ITC-0KZCWKi2eL	2024-12-09 12:23:51	2024-12-09 12:23:51
1353	PP-ChlfjAWDwj	28.49876027	ITC-XbiBgsSJkM	2024-12-09 12:23:51	2024-12-09 12:23:51
1354	PP-H5JXcI8Jo0	2.33772365	ITC-2KcILuMvKT	2024-12-09 12:23:51	2024-12-09 12:23:51
1355	PP-HJrQaohNmM	12.92516883	ITC-QuCgap9eFI	2024-12-09 12:23:51	2024-12-09 12:23:51
1356	PP-23Qi9VYO4X	52.99845579	ITC-G5BiAoEKzU	2024-12-09 12:23:51	2024-12-09 12:23:51
1357	PP-FlwRb96RiA	31.09411714	ITC-NK4e9qdvdM	2024-12-09 12:23:51	2024-12-09 12:23:51
1358	PP-JC6G02DfI7	7.83975531	ITC-7rUVFjBAdo	2024-12-09 12:23:51	2024-12-09 12:23:51
1359	PP-NIgf1nAHoy	1.85161200	ITC-wKyyQSfDUp	2024-12-09 12:23:51	2024-12-09 12:23:51
1360	PP-pOrMWbNXYQ	1.85161200	ITC-mFhh9aMM9V	2024-12-09 12:23:51	2024-12-09 12:23:51
1361	PP-bbSyKAiSzU	0.38635564	ITC-SNfUA4zPX9	2024-12-09 12:23:51	2024-12-09 12:23:51
1362	PP-saorFMZb5k	2.25806700	ITC-oa4hGuM7UC	2024-12-09 12:23:51	2024-12-09 12:23:51
1363	PP-DZ64826rEW	0.00000000	ITC-JGR3FfLxC6	2024-12-09 12:23:51	2024-12-09 12:23:51
1364	PP-Ej69ENqQ83	2.53139052	ITC-YsOvuKKHva	2024-12-09 12:23:51	2024-12-09 12:23:51
1365	PP-PBcSCARNEs	2.06940094	ITC-stchngBjHP	2024-12-09 12:23:51	2024-12-09 12:23:51
1366	PP-dZll1TuSTf	0.38410319	ITC-wIwSqATAWE	2024-12-09 12:23:51	2024-12-09 12:23:51
1367	PP-43KPiNbQEJ	2.14433800	ITC-ze20g04VB2	2024-12-09 12:23:51	2024-12-09 12:23:51
1368	PP-0SC53DfiTc	118.23509682	ITC-QXGzxMvmKL	2024-12-09 12:23:51	2024-12-09 12:23:51
1369	PP-uea42aiFx3	2.31355540	ITC-YyyMDt1mKa	2024-12-09 12:23:51	2024-12-09 12:23:51
1370	PP-FJgkEI6YAv	2.55931799	ITC-UYLbRC56Pw	2024-12-09 12:23:51	2024-12-09 12:23:51
1371	PP-TzlkL9BwG8	2.53828065	ITC-hKEcezNxWC	2024-12-09 12:23:51	2024-12-09 12:23:51
1372	PP-C3A5T9aNzE	3.72937805	ITC-Dcs5Y9nS5L	2024-12-09 12:23:51	2024-12-09 12:23:51
1373	PP-DWxEx3XbjI	2.14550749	ITC-tZg0t4X5xN	2024-12-09 12:23:51	2024-12-09 12:23:51
1374	PP-EwW7JMwlUO	2.34838968	ITC-G5Ad3PJneB	2024-12-09 12:23:51	2024-12-09 12:23:51
1375	PP-sGwVTaoq0d	2.10650323	ITC-nhcMdc7OyW	2024-12-09 12:23:51	2024-12-09 12:23:51
1376	PP-02DQcGRDNh	2.10650323	ITC-m7i1VBxXNm	2024-12-09 12:23:51	2024-12-09 12:23:51
1377	PP-XjWI8vr0VE	19.89965541	ITC-y9rHXdOAhj	2024-12-09 12:23:51	2024-12-09 12:23:51
1378	PP-2lxlYYWHMG	25.99035117	ITC-GvO24AQJZE	2024-12-09 12:23:51	2024-12-09 12:23:51
1379	PP-KLq30EhaM9	27.27744936	ITC-IKyyNjzZE0	2024-12-09 12:23:51	2024-12-09 12:23:51
1380	PP-B1kg0j8NVW	4.55005768	ITC-f55SXSrqrR	2024-12-09 12:23:51	2024-12-09 12:23:51
1381	PP-ZKWRbdHiNY	22.38703915	ITC-Igk7zIiFps	2024-12-09 12:23:51	2024-12-09 12:23:51
1382	PP-oM3diUaUFH	1.85161200	ITC-pr2IoJIB0z	2024-12-09 12:23:51	2024-12-09 12:23:51
1383	PP-KqhQmjEyD6	179.81219606	ITC-vAwQY8gH2H	2024-12-09 12:23:51	2024-12-09 12:23:51
1384	PP-8Rpmb7Tho4	1.99369357	ITC-0zjnsbw0wu	2024-12-09 12:23:51	2024-12-09 12:23:51
1385	PP-pA8xGqSlFC	27.22958891	ITC-MO95F6ARHa	2024-12-09 12:23:51	2024-12-09 12:23:51
1386	PP-mQYqAOU9Dp	27.22958891	ITC-AySSuDO0gi	2024-12-09 12:23:51	2024-12-09 12:23:51
1387	PP-LdQ3YsGjbq	25.96777050	ITC-lykjDQVfnF	2024-12-09 12:23:51	2024-12-09 12:23:51
1388	PP-YFThmzCbTK	51.41618559	ITC-J7EPLe7ruL	2024-12-09 12:23:51	2024-12-09 12:23:51
1389	PP-Y1f9CzTYT9	3.70322400	ITC-dI9XrtHs6d	2024-12-09 12:23:51	2024-12-09 12:23:51
1390	PP-MdQBeyBbqF	1.95549455	ITC-3Rh4ox62hn	2024-12-09 12:23:51	2024-12-09 12:23:51
1391	PP-6coxIoi9Jv	9.41713234	ITC-lvrnrUsdGJ	2024-12-09 12:23:51	2024-12-09 12:23:51
1392	PP-Aq5W0JC7Rb	67.54177785	ITC-FjvE5ODTlu	2024-12-09 12:23:51	2024-12-09 12:23:51
1393	PP-Ir6RS7eINt	21.72260454	ITC-N6BOPMHJ5z	2024-12-09 12:23:51	2024-12-09 12:23:51
1394	PP-z9mYzsswCA	1.88692521	ITC-oMN46k7kan	2024-12-09 12:23:51	2024-12-09 12:23:51
1395	PP-FnyPwKAXGq	1.85161200	ITC-m3MRU2bJ9F	2024-12-09 12:23:51	2024-12-09 12:23:51
1396	PP-68Woh5uKn4	1.88692521	ITC-Z692r80YQL	2024-12-09 12:23:51	2024-12-09 12:23:51
1397	PP-u56mfwpHzt	1.85161200	ITC-GgvO41uKHy	2024-12-09 12:23:51	2024-12-09 12:23:51
1398	PP-9Tjr5iNzSr	1.85161200	ITC-xNQZsYmvwv	2024-12-09 12:23:51	2024-12-09 12:23:51
1399	PP-LMy3IHuuxa	39.06242478	ITC-GQFm3SnCSL	2024-12-16 05:48:12	2024-12-16 05:48:12
1400	PP-76Ki9sgGkt	31.20103195	ITC-e8x2GsZeYs	2024-12-16 05:48:12	2024-12-16 05:48:12
1401	PP-bhgdbHKJBr	25.58601440	ITC-IzyqsIsBho	2024-12-16 05:48:12	2024-12-16 05:48:12
1402	PP-iEh3l9FciL	19.14987679	ITC-luUiKG6k2m	2024-12-16 05:48:12	2024-12-16 05:48:12
1403	PP-E8QNDAzfOF	29.15711197	ITC-8zlKdVTEKb	2024-12-16 05:48:12	2024-12-16 05:48:12
1404	PP-BjFHPF9w9B	19.22802778	ITC-Z0CMEPUbwC	2024-12-16 05:48:12	2024-12-16 05:48:12
1405	PP-xTfuEllSGo	9.54994071	ITC-5Z9isUEMMA	2024-12-16 05:48:12	2024-12-16 05:48:12
1406	PP-2vBfeuQf5P	14.81289600	ITC-FX6dgjpbdM	2024-12-16 05:48:12	2024-12-16 05:48:12
1407	PP-JbiqWowZfh	11.94314867	ITC-FIuyoYe0ta	2024-12-16 05:48:12	2024-12-16 05:48:12
1408	PP-1FZunxVnYd	12.96128400	ITC-lALXYPYWQ7	2024-12-16 05:48:12	2024-12-16 05:48:12
1409	PP-33tDaJ13jI	18.51612000	ITC-dHT7IzMEbg	2024-12-16 05:48:12	2024-12-16 05:48:12
1410	PP-ByWH1LJPVD	8.03661637	ITC-QRO4BEQ6wZ	2024-12-16 05:48:12	2024-12-16 05:48:12
1411	PP-v9DEsFpoV7	26.19309776	ITC-WHqNMXYAxy	2024-12-16 05:48:12	2024-12-16 05:48:12
1412	PP-WfdXsJLsvY	13.31833512	ITC-7ApObtH2pi	2024-12-16 05:48:12	2024-12-16 05:48:12
1413	PP-zvWmlRR5v7	41.19938456	ITC-lWkgBm6M8y	2024-12-16 05:48:12	2024-12-16 05:48:12
1414	PP-psyO9Ml7LX	331.51917847	ITC-XKDP9jfHOn	2024-12-16 05:48:12	2024-12-16 05:48:12
1415	PP-8x7DnolMER	54.66248906	ITC-D7q0DLwZ4y	2024-12-16 05:48:12	2024-12-16 05:48:12
1416	PP-zuZS4t0hsc	5.05490076	ITC-MmPRFIosoQ	2024-12-16 05:48:12	2024-12-16 05:48:12
1417	PP-Z9M37R5WWU	7.30925675	ITC-1h9KUgir50	2024-12-16 05:48:12	2024-12-16 05:48:12
1418	PP-RYr5LLK1Jf	13.94215243	ITC-1DHfDk80aX	2024-12-16 05:48:12	2024-12-16 05:48:12
1419	PP-SdPJ6Q2HOK	11.82934237	ITC-DlaAptpq2c	2024-12-16 05:48:12	2024-12-16 05:48:12
1420	PP-zFnyzVo7a7	22.60113035	ITC-1e2g490fDv	2024-12-16 05:48:12	2024-12-16 05:48:12
1421	PP-80OAT59Nhg	33.64262659	ITC-xWYNRrs8yJ	2024-12-16 05:48:12	2024-12-16 05:48:12
1422	PP-tiNkTW7D76	7.24800889	ITC-5SKQtT3vyf	2024-12-16 05:48:12	2024-12-16 05:48:12
1423	PP-3rbRpRRZYn	24.83873700	ITC-4lqsfdy1G3	2024-12-16 05:48:12	2024-12-16 05:48:12
1424	PP-RXHK39llFe	18.53463612	ITC-BKrKKpzteK	2024-12-16 05:48:12	2024-12-16 05:48:12
1425	PP-kCGDfbmZsP	17.59978341	ITC-ncLOsPXpV0	2024-12-16 05:48:12	2024-12-16 05:48:12
1426	PP-ePI1JTj1gW	27.09679000	ITC-gngFgoaoGi	2024-12-16 05:48:12	2024-12-16 05:48:12
1427	PP-IBBL5UjdQK	27.09679000	ITC-zr840ljEPc	2024-12-16 05:48:12	2024-12-16 05:48:12
1428	PP-3xfwmc5nn6	36.84799584	ITC-p9IWpWM5vc	2024-12-16 05:48:12	2024-12-16 05:48:12
1429	PP-TlzZxRbAcz	18.08631508	ITC-flY9k7HKFl	2024-12-16 05:48:12	2024-12-16 05:48:12
1430	PP-jx10Bq6fLQ	14.48578406	ITC-ft4j6MrmEp	2024-12-16 05:48:12	2024-12-16 05:48:12
1431	PP-h6jvrWnCjI	145.04911687	ITC-lcMghoXuxS	2024-12-16 05:48:12	2024-12-16 05:48:12
1432	PP-QLCISUMaQK	296.93787812	ITC-NHnbmFh8Ej	2024-12-16 05:48:12	2024-12-16 05:48:12
1433	PP-c5FoqymqfU	39.00516614	ITC-ncZ6SSLhOc	2024-12-16 05:48:12	2024-12-16 05:48:12
1434	PP-eP8vtHn2CQ	45.16134000	ITC-3uK8WuMYbf	2024-12-16 05:48:12	2024-12-16 05:48:12
1435	PP-25YnrzKLel	18.42453466	ITC-wi4u5b0jdJ	2024-12-16 05:48:12	2024-12-16 05:48:12
1436	PP-Ji9Rw2VTcd	4.71911338	ITC-0KZCWKi2eL	2024-12-16 05:48:12	2024-12-16 05:48:12
1437	PP-b3Yozk8HpE	28.49876027	ITC-XbiBgsSJkM	2024-12-16 05:48:12	2024-12-16 05:48:12
1438	PP-9xizJbbg7v	2.33772365	ITC-2KcILuMvKT	2024-12-16 05:48:12	2024-12-16 05:48:12
1439	PP-uB6N2sya1B	12.92516883	ITC-QuCgap9eFI	2024-12-16 05:48:12	2024-12-16 05:48:12
1440	PP-KFBVC9Ktvc	52.99845579	ITC-G5BiAoEKzU	2024-12-16 05:48:12	2024-12-16 05:48:12
1441	PP-lwP6F8DZIe	31.09411714	ITC-NK4e9qdvdM	2024-12-16 05:48:12	2024-12-16 05:48:12
1442	PP-sxt32xbkYs	7.83975531	ITC-7rUVFjBAdo	2024-12-16 05:48:12	2024-12-16 05:48:12
1443	PP-aG3e3W1Yk3	1.85161200	ITC-wKyyQSfDUp	2024-12-16 05:48:13	2024-12-16 05:48:13
1444	PP-xiOz7QGor8	1.85161200	ITC-mFhh9aMM9V	2024-12-16 05:48:13	2024-12-16 05:48:13
1445	PP-kxU41cEJWN	0.38635564	ITC-SNfUA4zPX9	2024-12-16 05:48:13	2024-12-16 05:48:13
1446	PP-6S8KTvSEGu	2.25806700	ITC-oa4hGuM7UC	2024-12-16 05:48:13	2024-12-16 05:48:13
1447	PP-rLHWWbBtp4	0.00000000	ITC-JGR3FfLxC6	2024-12-16 05:48:13	2024-12-16 05:48:13
1448	PP-5DhpkPUhzt	2.57826205	ITC-YsOvuKKHva	2024-12-16 05:48:13	2024-12-16 05:48:13
1449	PP-iUAggYtWT6	2.06940094	ITC-stchngBjHP	2024-12-16 05:48:13	2024-12-16 05:48:13
1450	PP-Zn8QKmZV45	0.39121529	ITC-wIwSqATAWE	2024-12-16 05:48:13	2024-12-16 05:48:13
1451	PP-8k6MUEubQ9	2.14433800	ITC-ze20g04VB2	2024-12-16 05:48:13	2024-12-16 05:48:13
1452	PP-pe5zZkooO3	120.90492452	ITC-QXGzxMvmKL	2024-12-16 05:48:13	2024-12-16 05:48:13
1453	PP-qQbw900XQQ	2.40051668	ITC-YyyMDt1mKa	2024-12-16 05:48:13	2024-12-16 05:48:13
1454	PP-7MlgzUfGcz	2.60670663	ITC-UYLbRC56Pw	2024-12-16 05:48:13	2024-12-16 05:48:13
1455	PP-04j2s6bnGH	2.58527976	ITC-hKEcezNxWC	2024-12-16 05:48:13	2024-12-16 05:48:13
1456	PP-vYRYwwce54	3.81358991	ITC-Dcs5Y9nS5L	2024-12-16 05:48:13	2024-12-16 05:48:13
1457	PP-3Cwn9X34nc	2.18523397	ITC-tZg0t4X5xN	2024-12-16 05:48:13	2024-12-16 05:48:13
1458	PP-CrDT0XTWTq	2.34838968	ITC-G5Ad3PJneB	2024-12-16 05:48:13	2024-12-16 05:48:13
1459	PP-1chJoxQFHe	2.14550749	ITC-nhcMdc7OyW	2024-12-16 05:48:13	2024-12-16 05:48:13
1460	PP-8ea0p3GuGP	2.14550749	ITC-m7i1VBxXNm	2024-12-16 05:48:13	2024-12-16 05:48:13
1461	PP-Xawb8d8VVy	19.89965541	ITC-y9rHXdOAhj	2024-12-16 05:48:13	2024-12-16 05:48:13
1462	PP-4g21rscDD2	25.99035117	ITC-GvO24AQJZE	2024-12-16 05:48:13	2024-12-16 05:48:13
1463	PP-8LOTYda8pk	29.75969998	ITC-IKyyNjzZE0	2024-12-16 05:48:13	2024-12-16 05:48:13
1464	PP-Ooh4KohrbC	4.63430710	ITC-f55SXSrqrR	2024-12-16 05:48:13	2024-12-16 05:48:13
1465	PP-KOzvoPjwft	22.38703915	ITC-Igk7zIiFps	2024-12-16 05:48:13	2024-12-16 05:48:13
1466	PP-bwLeKOASPS	1.85161200	ITC-pr2IoJIB0z	2024-12-16 05:48:13	2024-12-16 05:48:13
1467	PP-vYDHHF0IJT	203.74346552	ITC-vAwQY8gH2H	2024-12-16 05:48:13	2024-12-16 05:48:13
1468	PP-RbhBfHOqPw	2.03060904	ITC-0zjnsbw0wu	2024-12-16 05:48:13	2024-12-16 05:48:13
1469	PP-ARbwRwE93O	27.84445127	ITC-MO95F6ARHa	2024-12-16 05:48:13	2024-12-16 05:48:13
1470	PP-CAIAjTlnXn	27.84445127	ITC-AySSuDO0gi	2024-12-16 05:48:13	2024-12-16 05:48:13
1471	PP-EXLdEa7wh1	25.96777050	ITC-lykjDQVfnF	2024-12-16 05:48:13	2024-12-16 05:48:13
1472	PP-lu9OquZxHB	51.41618559	ITC-J7EPLe7ruL	2024-12-16 05:48:13	2024-12-16 05:48:13
1473	PP-0WnklOGA7S	3.70322400	ITC-dI9XrtHs6d	2024-12-16 05:48:13	2024-12-16 05:48:13
1474	PP-WQnDYAsnCy	1.95549455	ITC-3Rh4ox62hn	2024-12-16 05:48:13	2024-12-16 05:48:13
1475	PP-bnfV2ll8P5	9.59150110	ITC-lvrnrUsdGJ	2024-12-16 05:48:13	2024-12-16 05:48:13
1476	PP-Ej4dt5NaML	71.55079015	ITC-FjvE5ODTlu	2024-12-16 05:48:13	2024-12-16 05:48:13
1477	PP-mlAPUYpEDr	21.72260454	ITC-N6BOPMHJ5z	2024-12-16 05:48:13	2024-12-16 05:48:13
1478	PP-2i9HcH4X62	1.92186374	ITC-oMN46k7kan	2024-12-16 05:48:13	2024-12-16 05:48:13
1479	PP-TeuxhdmLUo	1.85161200	ITC-m3MRU2bJ9F	2024-12-16 05:48:13	2024-12-16 05:48:13
1480	PP-5yI0BB4WRn	1.92186374	ITC-Z692r80YQL	2024-12-16 05:48:13	2024-12-16 05:48:13
1481	PP-qjii4OrFxd	1.92120988	ITC-GgvO41uKHy	2024-12-16 05:48:13	2024-12-16 05:48:13
1482	PP-HI8JO1b7Hp	1.85161200	ITC-xNQZsYmvwv	2024-12-16 05:48:13	2024-12-16 05:48:13
1483	PP-qZDOylPyot	18.18282984	ITC-gKLELcDX6G	2024-12-16 05:48:13	2024-12-16 05:48:13
1484	PP-XytuUlIi9y	54.64522140	ITC-S6dbIk79cE	2024-12-16 05:48:13	2024-12-16 05:48:13
1485	PP-OLQTpcZUhC	40.12089110	ITC-GQFm3SnCSL	2024-12-23 07:38:44	2024-12-23 07:38:44
1486	PP-uzSzQI3Qzp	31.90557216	ITC-e8x2GsZeYs	2024-12-23 07:38:44	2024-12-23 07:38:44
1487	PP-1epvvEUh1w	26.16376375	ITC-IzyqsIsBho	2024-12-23 07:38:44	2024-12-23 07:38:44
1488	PP-6xbjtqF81t	19.14987679	ITC-luUiKG6k2m	2024-12-23 07:38:44	2024-12-23 07:38:44
1489	PP-YZKaS0Xu2E	29.15711197	ITC-8zlKdVTEKb	2024-12-23 07:38:44	2024-12-23 07:38:44
1490	PP-YYiwdxzX9X	19.22802778	ITC-Z0CMEPUbwC	2024-12-23 07:38:44	2024-12-23 07:38:44
1491	PP-iRn7T4bjig	9.54994071	ITC-5Z9isUEMMA	2024-12-23 07:38:44	2024-12-23 07:38:44
1492	PP-yMAGXv0Bjp	14.81289600	ITC-FX6dgjpbdM	2024-12-23 07:38:44	2024-12-23 07:38:44
1493	PP-QCumXR2LA0	11.94314867	ITC-FIuyoYe0ta	2024-12-23 07:38:44	2024-12-23 07:38:44
1494	PP-PlkHm4nuN2	12.96128400	ITC-lALXYPYWQ7	2024-12-23 07:38:44	2024-12-23 07:38:44
1495	PP-hZT7Cmj9Ge	18.51612000	ITC-dHT7IzMEbg	2024-12-23 07:38:44	2024-12-23 07:38:44
1496	PP-dbJXTwadHk	8.03661637	ITC-QRO4BEQ6wZ	2024-12-23 07:38:44	2024-12-23 07:38:44
1497	PP-pW5MJgWIEe	26.19309776	ITC-WHqNMXYAxy	2024-12-23 07:38:44	2024-12-23 07:38:44
1498	PP-0onwAIPd23	13.31833512	ITC-7ApObtH2pi	2024-12-23 07:38:44	2024-12-23 07:38:44
1499	PP-x6Mg0SW9L2	41.18130613	ITC-lWkgBm6M8y	2024-12-23 07:38:44	2024-12-23 07:38:44
1500	PP-jBTBQ6qaub	331.51917847	ITC-XKDP9jfHOn	2024-12-23 07:38:44	2024-12-23 07:38:44
1501	PP-rQNDPz6D4f	54.66248906	ITC-D7q0DLwZ4y	2024-12-23 07:38:44	2024-12-23 07:38:44
1502	PP-luvt1NmsOS	5.05490076	ITC-MmPRFIosoQ	2024-12-23 07:38:44	2024-12-23 07:38:44
1503	PP-wrmBQ2LIbQ	7.44459582	ITC-1h9KUgir50	2024-12-23 07:38:44	2024-12-23 07:38:44
1504	PP-ERYHNuBfMc	13.94215243	ITC-1DHfDk80aX	2024-12-23 07:38:44	2024-12-23 07:38:44
1505	PP-tUbrrobUy9	11.82934237	ITC-DlaAptpq2c	2024-12-23 07:38:44	2024-12-23 07:38:44
1506	PP-J9dtnmEunC	22.60113035	ITC-1e2g490fDv	2024-12-23 07:38:44	2024-12-23 07:38:44
1507	PP-9jwUNByXAD	33.64262659	ITC-xWYNRrs8yJ	2024-12-23 07:38:44	2024-12-23 07:38:44
1508	PP-ho18WUDb1v	7.24800889	ITC-5SKQtT3vyf	2024-12-23 07:38:44	2024-12-23 07:38:44
1509	PP-9ojXbqV3nN	24.83873700	ITC-4lqsfdy1G3	2024-12-23 07:38:44	2024-12-23 07:38:44
1510	PP-Zmg7Z8pjUd	18.53463612	ITC-BKrKKpzteK	2024-12-23 07:38:44	2024-12-23 07:38:44
1511	PP-AhDx05FVji	17.59978341	ITC-ncLOsPXpV0	2024-12-23 07:38:44	2024-12-23 07:38:44
1512	PP-yBlSeeO1Ou	27.09679000	ITC-gngFgoaoGi	2024-12-23 07:38:44	2024-12-23 07:38:44
1513	PP-qAC64aaWfo	27.09679000	ITC-zr840ljEPc	2024-12-23 07:38:44	2024-12-23 07:38:44
1514	PP-wlXhm1hDuY	37.68004827	ITC-p9IWpWM5vc	2024-12-23 07:38:44	2024-12-23 07:38:44
1515	PP-z98ESxoYKt	18.49471619	ITC-flY9k7HKFl	2024-12-23 07:38:44	2024-12-23 07:38:44
1516	PP-Al4WNhi1tv	14.48578406	ITC-ft4j6MrmEp	2024-12-23 07:38:44	2024-12-23 07:38:44
1517	PP-KWkuO5HLN3	145.04911687	ITC-lcMghoXuxS	2024-12-23 07:38:44	2024-12-23 07:38:44
1518	PP-3GOPSGx9hJ	296.93787812	ITC-NHnbmFh8Ej	2024-12-23 07:38:44	2024-12-23 07:38:44
1519	PP-kq5jA6hi27	39.88592893	ITC-ncZ6SSLhOc	2024-12-23 07:38:44	2024-12-23 07:38:44
1520	PP-qj5xh6usi5	45.16134000	ITC-3uK8WuMYbf	2024-12-23 07:38:44	2024-12-23 07:38:44
1521	PP-Q8hIm2FXna	18.84057300	ITC-wi4u5b0jdJ	2024-12-23 07:38:44	2024-12-23 07:38:44
1522	PP-gY9l9e0XDA	4.71911338	ITC-0KZCWKi2eL	2024-12-23 07:38:44	2024-12-23 07:38:44
1523	PP-sXDBbVTbv8	28.49876027	ITC-XbiBgsSJkM	2024-12-23 07:38:44	2024-12-23 07:38:44
1524	PP-8GjRXkNeov	2.33772365	ITC-2KcILuMvKT	2024-12-23 07:38:44	2024-12-23 07:38:44
1525	PP-82atRqa57c	12.92516883	ITC-QuCgap9eFI	2024-12-23 07:38:44	2024-12-23 07:38:44
1526	PP-TRzprDJgSr	52.99845579	ITC-G5BiAoEKzU	2024-12-23 07:38:44	2024-12-23 07:38:44
1527	PP-L5Uwgj7GI4	31.09411714	ITC-NK4e9qdvdM	2024-12-23 07:38:44	2024-12-23 07:38:44
1528	PP-ZJoEJxw8Au	7.83975531	ITC-7rUVFjBAdo	2024-12-23 07:38:44	2024-12-23 07:38:44
1529	PP-hPnXzj7poI	0.00000000	ITC-wKyyQSfDUp	2024-12-23 07:38:44	2024-12-23 07:38:44
1530	PP-baKmChZI9Z	1.85161200	ITC-mFhh9aMM9V	2024-12-23 07:38:44	2024-12-23 07:38:44
1531	PP-iKlV8mlSnw	0.38635564	ITC-SNfUA4zPX9	2024-12-23 07:38:44	2024-12-23 07:38:44
1532	PP-sOAO7PPPtc	2.25806700	ITC-oa4hGuM7UC	2024-12-23 07:38:44	2024-12-23 07:38:44
1533	PP-uu48KYCQw3	0.00000000	ITC-JGR3FfLxC6	2024-12-23 07:38:44	2024-12-23 07:38:44
1534	PP-s1UvZQ3JJk	2.62600146	ITC-YsOvuKKHva	2024-12-23 07:38:44	2024-12-23 07:38:44
1535	PP-iVYaSHa45V	2.06940094	ITC-stchngBjHP	2024-12-23 07:38:44	2024-12-23 07:38:44
1536	PP-seSgIXNYqe	0.39845908	ITC-wIwSqATAWE	2024-12-23 07:38:44	2024-12-23 07:38:44
1537	PP-Q650DuMDME	2.14433800	ITC-ze20g04VB2	2024-12-23 07:38:44	2024-12-23 07:38:44
1538	PP-wtNEUjmLwH	123.63503872	ITC-QXGzxMvmKL	2024-12-23 07:38:44	2024-12-23 07:38:44
1539	PP-hV6ODCgl0p	2.44496493	ITC-YyyMDt1mKa	2024-12-23 07:38:44	2024-12-23 07:38:44
1540	PP-yXLVtGMK4v	2.65497272	ITC-UYLbRC56Pw	2024-12-23 07:38:44	2024-12-23 07:38:44
1541	PP-eXIgbr3oQk	0.78153711	ITC-hKEcezNxWC	2024-12-23 07:38:44	2024-12-23 07:38:44
1542	PP-wZLz0Ty3wW	3.89970332	ITC-Dcs5Y9nS5L	2024-12-23 07:38:44	2024-12-23 07:38:44
1543	PP-Bc1jqAOYtD	2.22569602	ITC-tZg0t4X5xN	2024-12-23 07:38:44	2024-12-23 07:38:44
1544	PP-DETbpYsJDR	2.34838968	ITC-G5Ad3PJneB	2024-12-23 07:38:44	2024-12-23 07:38:44
1545	PP-941MQVBJYa	2.18523397	ITC-nhcMdc7OyW	2024-12-23 07:38:44	2024-12-23 07:38:44
1546	PP-olu093yRFE	0.33362197	ITC-m7i1VBxXNm	2024-12-23 07:38:44	2024-12-23 07:38:44
1547	PP-nWrDA18tJd	19.89965541	ITC-y9rHXdOAhj	2024-12-23 07:38:44	2024-12-23 07:38:44
1548	PP-aKjotggqiK	25.99035117	ITC-GvO24AQJZE	2024-12-23 07:38:44	2024-12-23 07:38:44
1549	PP-4NyJX5aumJ	29.75969998	ITC-IKyyNjzZE0	2024-12-23 07:38:44	2024-12-23 07:38:44
1550	PP-hNYy2jnOLG	4.72011648	ITC-f55SXSrqrR	2024-12-23 07:38:44	2024-12-23 07:38:44
1551	PP-enkuQJqROI	115.07558804	ITC-Igk7zIiFps	2024-12-23 07:38:44	2024-12-23 07:38:44
1552	PP-65mZ9kf2jG	1.85161200	ITC-pr2IoJIB0z	2024-12-23 07:38:44	2024-12-23 07:38:44
1553	PP-gKpv6bV0CT	208.34412948	ITC-vAwQY8gH2H	2024-12-23 07:38:44	2024-12-23 07:38:44
1554	PP-fzNsUM4cZS	2.06820804	ITC-0zjnsbw0wu	2024-12-23 07:38:44	2024-12-23 07:38:44
1555	PP-xeGWVDKO4w	28.47319764	ITC-MO95F6ARHa	2024-12-23 07:38:44	2024-12-23 07:38:44
1556	PP-qTnik59DG2	28.47319764	ITC-AySSuDO0gi	2024-12-23 07:38:44	2024-12-23 07:38:44
1557	PP-YP9j5FdnZQ	25.96777050	ITC-lykjDQVfnF	2024-12-23 07:38:44	2024-12-23 07:38:44
1558	PP-wqNSCuLoA6	51.41618559	ITC-J7EPLe7ruL	2024-12-23 07:38:44	2024-12-23 07:38:44
1559	PP-u4cIsFDBVt	3.70322400	ITC-dI9XrtHs6d	2024-12-23 07:38:44	2024-12-23 07:38:44
1560	PP-DboUhzx2ub	1.95549455	ITC-3Rh4ox62hn	2024-12-23 07:38:44	2024-12-23 07:38:44
1561	PP-bBgZkZBYpr	9.76909848	ITC-lvrnrUsdGJ	2024-12-23 07:38:44	2024-12-23 07:38:44
1562	PP-W8eyOZ8NR1	95.06970483	ITC-FjvE5ODTlu	2024-12-23 07:38:44	2024-12-23 07:38:44
1563	PP-J2f7MugwQS	21.72260454	ITC-N6BOPMHJ5z	2024-12-23 07:38:44	2024-12-23 07:38:44
1564	PP-UjKvGaGWL1	1.95744920	ITC-oMN46k7kan	2024-12-23 07:38:44	2024-12-23 07:38:44
1565	PP-ak0gRMook2	1.85161200	ITC-m3MRU2bJ9F	2024-12-23 07:38:44	2024-12-23 07:38:44
1566	PP-w9uvoEVfjR	1.95744920	ITC-Z692r80YQL	2024-12-23 07:38:44	2024-12-23 07:38:44
1567	PP-3w0dvu2AZ2	1.95678323	ITC-GgvO41uKHy	2024-12-23 07:38:44	2024-12-23 07:38:44
1568	PP-qjGZdNRg5R	1.85161200	ITC-xNQZsYmvwv	2024-12-23 07:38:44	2024-12-23 07:38:44
1569	PP-GkisnNmzhp	56.45167500	ITC-gKLELcDX6G	2024-12-23 07:38:44	2024-12-23 07:38:44
1570	PP-0szueejBYx	55.87914711	ITC-S6dbIk79cE	2024-12-23 07:38:44	2024-12-23 07:38:44
1571	PP-iZ3f0IwmiC	40.12089110	ITC-GQFm3SnCSL	2024-12-29 21:27:38	2024-12-29 21:27:38
1572	PP-X2iYOXg5GZ	31.90557216	ITC-e8x2GsZeYs	2024-12-29 21:27:38	2024-12-29 21:27:38
1573	PP-GTNXK8GzwG	26.75455906	ITC-IzyqsIsBho	2024-12-29 21:27:38	2024-12-29 21:27:38
1574	PP-ZXwCO5QOOJ	19.14987679	ITC-luUiKG6k2m	2024-12-29 21:27:38	2024-12-29 21:27:38
1575	PP-mF9lLVjGmQ	29.15711197	ITC-8zlKdVTEKb	2024-12-29 21:27:38	2024-12-29 21:27:38
1576	PP-2PMkisLwey	19.22802778	ITC-Z0CMEPUbwC	2024-12-29 21:27:38	2024-12-29 21:27:38
1577	PP-U8BysN1U3l	9.54994071	ITC-5Z9isUEMMA	2024-12-29 21:27:38	2024-12-29 21:27:38
1578	PP-va8cZBbpmI	14.81289600	ITC-FX6dgjpbdM	2024-12-29 21:27:38	2024-12-29 21:27:38
1579	PP-4SpewNo87D	12.21283297	ITC-FIuyoYe0ta	2024-12-29 21:27:38	2024-12-29 21:27:38
1580	PP-2PpxzXDqqP	12.96128400	ITC-lALXYPYWQ7	2024-12-29 21:27:38	2024-12-29 21:27:38
1581	PP-VVav7UYPlC	18.51612000	ITC-dHT7IzMEbg	2024-12-29 21:27:38	2024-12-29 21:27:38
1582	PP-EIVOXbi00i	8.03661637	ITC-QRO4BEQ6wZ	2024-12-29 21:27:38	2024-12-29 21:27:38
1583	PP-2QHJskOmEQ	26.78455546	ITC-WHqNMXYAxy	2024-12-29 21:27:38	2024-12-29 21:27:38
1584	PP-wGpjCWiD2a	13.61907205	ITC-7ApObtH2pi	2024-12-29 21:27:38	2024-12-29 21:27:38
1585	PP-jsK6aA5CXI	42.11120761	ITC-lWkgBm6M8y	2024-12-29 21:27:38	2024-12-29 21:27:38
1586	PP-w9i9fDP4Dg	339.75369383	ITC-XKDP9jfHOn	2024-12-29 21:27:38	2024-12-29 21:27:38
1587	PP-KeF7CriNnT	55.67462627	ITC-D7q0DLwZ4y	2024-12-29 21:27:38	2024-12-29 21:27:38
1588	PP-hRhyfc1vTp	5.05490076	ITC-MmPRFIosoQ	2024-12-29 21:27:38	2024-12-29 21:27:38
1589	PP-Bbt8GgSK6J	7.44459582	ITC-1h9KUgir50	2024-12-29 21:27:38	2024-12-29 21:27:38
1590	PP-H12CKG7ZoO	13.94215243	ITC-1DHfDk80aX	2024-12-29 21:27:38	2024-12-29 21:27:38
1591	PP-9vOxeoAU0V	11.82934237	ITC-DlaAptpq2c	2024-12-29 21:27:38	2024-12-29 21:27:38
1592	PP-0uHu7n218E	22.60113035	ITC-1e2g490fDv	2024-12-29 21:27:38	2024-12-29 21:27:38
1593	PP-vGiPTdyqjH	33.64262659	ITC-xWYNRrs8yJ	2024-12-29 21:27:38	2024-12-29 21:27:38
1594	PP-eXaDjjQdTm	7.24800889	ITC-5SKQtT3vyf	2024-12-29 21:27:38	2024-12-29 21:27:38
1595	PP-7WFl10a0qc	24.83873700	ITC-4lqsfdy1G3	2024-12-29 21:27:38	2024-12-29 21:27:38
1596	PP-HCvBF4lN7m	18.53463612	ITC-BKrKKpzteK	2024-12-29 21:27:38	2024-12-29 21:27:38
1597	PP-u1CYyzU9e1	17.59978341	ITC-ncLOsPXpV0	2024-12-29 21:27:38	2024-12-29 21:27:38
1598	PP-3ERBeUSD5j	27.09679000	ITC-gngFgoaoGi	2024-12-29 21:27:38	2024-12-29 21:27:38
1599	PP-QfwUvd9WjR	27.09679000	ITC-zr840ljEPc	2024-12-29 21:27:38	2024-12-29 21:27:38
1600	PP-M8r2roxnrT	38.53088901	ITC-p9IWpWM5vc	2024-12-29 21:27:38	2024-12-29 21:27:38
1601	PP-Q2T8xKyDT3	18.91233928	ITC-flY9k7HKFl	2024-12-29 21:27:38	2024-12-29 21:27:38
1602	PP-NzQExw9Gku	14.48578406	ITC-ft4j6MrmEp	2024-12-29 21:27:38	2024-12-29 21:27:38
1603	PP-ygaKxUkVvb	145.04911687	ITC-lcMghoXuxS	2024-12-29 21:27:38	2024-12-29 21:27:38
1604	PP-VLDCdNXt4T	296.93787812	ITC-NHnbmFh8Ej	2024-12-29 21:27:38	2024-12-29 21:27:38
1605	PP-3Z0jK1czhI	40.78657993	ITC-ncZ6SSLhOc	2024-12-29 21:27:38	2024-12-29 21:27:38
1606	PP-iqiqrdT4kv	45.16134000	ITC-3uK8WuMYbf	2024-12-29 21:27:38	2024-12-29 21:27:38
1607	PP-J0OHBde9hB	19.26600576	ITC-wi4u5b0jdJ	2024-12-29 21:27:38	2024-12-29 21:27:38
1608	PP-UwrjHetRRV	4.71911338	ITC-0KZCWKi2eL	2024-12-29 21:27:38	2024-12-29 21:27:38
1609	PP-jmJw75TdwU	29.14228137	ITC-XbiBgsSJkM	2024-12-29 21:27:38	2024-12-29 21:27:38
1610	PP-QXmyDksrdg	2.33772365	ITC-2KcILuMvKT	2024-12-29 21:27:38	2024-12-29 21:27:38
1611	PP-lXhZlGM0uH	12.92516883	ITC-QuCgap9eFI	2024-12-29 21:27:38	2024-12-29 21:27:38
1612	PP-SH9FR6moQr	54.19519643	ITC-G5BiAoEKzU	2024-12-29 21:27:38	2024-12-29 21:27:38
1613	PP-IUwlm20tFJ	31.09411714	ITC-NK4e9qdvdM	2024-12-29 21:27:38	2024-12-29 21:27:38
1614	PP-W0VZWYnvoY	7.83975531	ITC-7rUVFjBAdo	2024-12-29 21:27:38	2024-12-29 21:27:38
1615	PP-oCX9axyftM	0.00000000	ITC-wKyyQSfDUp	2024-12-29 21:27:38	2024-12-29 21:27:38
1616	PP-4EwsrlAGIp	1.88589667	ITC-mFhh9aMM9V	2024-12-29 21:27:38	2024-12-29 21:27:38
1617	PP-ryivWApNOq	0.38635564	ITC-SNfUA4zPX9	2024-12-29 21:27:38	2024-12-29 21:27:38
1618	PP-CNDKYNqlo2	2.25806700	ITC-oa4hGuM7UC	2024-12-29 21:27:38	2024-12-29 21:27:38
1619	PP-w1qYQGSxNZ	0.00000000	ITC-JGR3FfLxC6	2024-12-29 21:27:38	2024-12-29 21:27:38
1620	PP-SDVqavh0Ti	2.67462482	ITC-YsOvuKKHva	2024-12-29 21:27:38	2024-12-29 21:27:38
1621	PP-iGNDSUsPfl	2.06940094	ITC-stchngBjHP	2024-12-29 21:27:38	2024-12-29 21:27:38
1622	PP-Dd3XJ8g49U	0.40583700	ITC-wIwSqATAWE	2024-12-29 21:27:38	2024-12-29 21:27:38
1623	PP-wXG7Bvc9Ia	2.14433800	ITC-ze20g04VB2	2024-12-29 21:27:38	2024-12-29 21:27:38
1624	PP-Pycivpt0LC	126.42680073	ITC-QXGzxMvmKL	2024-12-29 21:27:38	2024-12-29 21:27:38
1625	PP-XzY6mHu4Hj	2.49023620	ITC-YyyMDt1mKa	2024-12-29 21:27:38	2024-12-29 21:27:38
1626	PP-NKA1hCxOht	2.70413251	ITC-UYLbRC56Pw	2024-12-29 21:27:38	2024-12-29 21:27:38
1627	PP-81CYv0H923	0.79600814	ITC-hKEcezNxWC	2024-12-29 21:27:38	2024-12-29 21:27:38
1628	PP-PdMFi5LCGm	3.98776124	ITC-Dcs5Y9nS5L	2024-12-29 21:27:38	2024-12-29 21:27:38
1629	PP-Z6jOpYTKvg	2.26690728	ITC-tZg0t4X5xN	2024-12-29 21:27:38	2024-12-29 21:27:38
1630	PP-7ognqV47ik	2.34838968	ITC-G5Ad3PJneB	2024-12-29 21:27:38	2024-12-29 21:27:38
1631	PP-HKSVR6xoF8	2.22569602	ITC-nhcMdc7OyW	2024-12-29 21:27:38	2024-12-29 21:27:38
1632	PP-Zg1A7ON4We	2.19141135	ITC-m7i1VBxXNm	2024-12-29 21:27:38	2024-12-29 21:27:38
1633	PP-VIM9bEEyfN	19.89965541	ITC-y9rHXdOAhj	2024-12-29 21:27:38	2024-12-29 21:27:38
1634	PP-i5PvR6qr0I	25.99035117	ITC-GvO24AQJZE	2024-12-29 21:27:38	2024-12-29 21:27:38
1635	PP-pAUwUDwZn2	29.75969998	ITC-IKyyNjzZE0	2024-12-29 21:27:38	2024-12-29 21:27:38
1636	PP-YSTsiUeAmL	4.80751473	ITC-f55SXSrqrR	2024-12-29 21:27:38	2024-12-29 21:27:38
1637	PP-rze2ANBNfv	115.07558804	ITC-Igk7zIiFps	2024-12-29 21:27:38	2024-12-29 21:27:38
1638	PP-9N0QJpftIf	1.85161200	ITC-pr2IoJIB0z	2024-12-29 21:27:38	2024-12-29 21:27:38
1639	PP-cwp3kHzEku	213.04867951	ITC-vAwQY8gH2H	2024-12-29 21:27:38	2024-12-29 21:27:38
1640	PP-KJldYoPCki	2.10650323	ITC-0zjnsbw0wu	2024-12-29 21:27:38	2024-12-29 21:27:38
1641	PP-A3xMhQhV84	29.11614152	ITC-MO95F6ARHa	2024-12-29 21:27:38	2024-12-29 21:27:38
1642	PP-AD8azurE4O	29.11614152	ITC-AySSuDO0gi	2024-12-29 21:27:38	2024-12-29 21:27:38
1643	PP-45Auq6XjvP	26.55414016	ITC-lykjDQVfnF	2024-12-29 21:27:38	2024-12-29 21:27:38
1644	PP-UE2EPvdBSG	51.41618559	ITC-J7EPLe7ruL	2024-12-29 21:27:38	2024-12-29 21:27:38
1645	PP-oSM1HaaBS0	3.70322400	ITC-dI9XrtHs6d	2024-12-29 21:27:38	2024-12-29 21:27:38
1646	PP-1uPbjwtv0N	1.95549455	ITC-3Rh4ox62hn	2024-12-29 21:27:38	2024-12-29 21:27:38
1647	PP-t1QcZo5IiK	9.94998428	ITC-lvrnrUsdGJ	2024-12-29 21:27:38	2024-12-29 21:27:38
1648	PP-Hvw3gSLApW	140.79713556	ITC-FjvE5ODTlu	2024-12-29 21:27:38	2024-12-29 21:27:38
1649	PP-afpGx4YWRW	21.72260454	ITC-N6BOPMHJ5z	2024-12-29 21:27:38	2024-12-29 21:27:38
1650	PP-0oRO836yOc	3.84530557	ITC-oMN46k7kan	2024-12-29 21:27:38	2024-12-29 21:27:38
1651	PP-8TekaomyO3	1.85161200	ITC-m3MRU2bJ9F	2024-12-29 21:27:38	2024-12-29 21:27:38
1652	PP-FCJs3HVzxA	1.99369357	ITC-Z692r80YQL	2024-12-29 21:27:38	2024-12-29 21:27:38
1653	PP-i2au0n3mqs	1.99301527	ITC-GgvO41uKHy	2024-12-29 21:27:38	2024-12-29 21:27:38
1654	PP-uZSpAQVy4S	1.85161200	ITC-xNQZsYmvwv	2024-12-29 21:27:38	2024-12-29 21:27:38
1655	PP-Y7l84KtG5H	58.13697212	ITC-gKLELcDX6G	2024-12-29 21:27:38	2024-12-29 21:27:38
1656	PP-WOM689Yi61	57.14093569	ITC-S6dbIk79cE	2024-12-29 21:27:38	2024-12-29 21:27:38
1657	PP-7QlduOSng0	112.90335000	ITC-uzsAOBkQp1	2024-12-29 21:27:38	2024-12-29 21:27:38
1658	PP-QP90oQfbuE	18.51612000	ITC-iSV1wESOKD	2024-12-29 21:27:38	2024-12-29 21:27:38
1659	PP-8qAivukmWE	1.85161200	ITC-urP93T9Md3	2024-12-29 21:27:38	2024-12-29 21:27:38
1660	PP-5XUdHnYUQz	41.20803846	ITC-GQFm3SnCSL	2025-01-05 21:46:45	2025-01-05 21:46:45
1661	PP-EhNPBLY7EO	32.62602136	ITC-e8x2GsZeYs	2025-01-05 21:46:45	2025-01-05 21:46:45
1662	PP-jsCgb4gRYw	26.75455906	ITC-IzyqsIsBho	2025-01-05 21:46:45	2025-01-05 21:46:45
1663	PP-EbEDZTGCPn	19.14987679	ITC-luUiKG6k2m	2025-01-05 21:46:45	2025-01-05 21:46:45
1664	PP-s3lojG2AOy	29.15711197	ITC-8zlKdVTEKb	2025-01-05 21:46:45	2025-01-05 21:46:45
1665	PP-Tki7muFUTn	19.22802778	ITC-Z0CMEPUbwC	2025-01-05 21:46:45	2025-01-05 21:46:45
1666	PP-2hkEWUec92	9.54994071	ITC-5Z9isUEMMA	2025-01-05 21:46:45	2025-01-05 21:46:45
1667	PP-5EwRv70di2	14.81289600	ITC-FX6dgjpbdM	2025-01-05 21:46:45	2025-01-05 21:46:45
1668	PP-3PCM7nx2yO	12.48860692	ITC-FIuyoYe0ta	2025-01-05 21:46:45	2025-01-05 21:46:45
1669	PP-k5oWBAymyf	12.96128400	ITC-lALXYPYWQ7	2025-01-05 21:46:45	2025-01-05 21:46:45
1670	PP-8zyMCmB54M	18.51612000	ITC-dHT7IzMEbg	2025-01-05 21:46:45	2025-01-05 21:46:45
1671	PP-vJS2MTjkLP	8.03661637	ITC-QRO4BEQ6wZ	2025-01-05 21:46:45	2025-01-05 21:46:45
1672	PP-GJefUGX1Th	27.38936867	ITC-WHqNMXYAxy	2025-01-05 21:46:45	2025-01-05 21:46:45
1673	PP-82VXAVuk2Q	13.92659983	ITC-7ApObtH2pi	2025-01-05 21:46:45	2025-01-05 21:46:45
1674	PP-87qm6eNO5d	43.06210689	ITC-lWkgBm6M8y	2025-01-05 21:46:45	2025-01-05 21:46:45
1675	PP-LEhvkBRx3N	348.19274410	ITC-XKDP9jfHOn	2025-01-05 21:46:45	2025-01-05 21:46:45
1676	PP-Prk7LvRuor	56.70550433	ITC-D7q0DLwZ4y	2025-01-05 21:46:45	2025-01-05 21:46:45
1677	PP-kcCAXHMgxh	5.05490076	ITC-MmPRFIosoQ	2025-01-05 21:46:45	2025-01-05 21:46:45
1678	PP-kK8QxqdPiH	7.72028588	ITC-1h9KUgir50	2025-01-05 21:46:45	2025-01-05 21:46:45
1679	PP-cmJsQqHhCU	13.94215243	ITC-1DHfDk80aX	2025-01-05 21:46:45	2025-01-05 21:46:45
1680	PP-6IgiZjI8VT	11.82934237	ITC-DlaAptpq2c	2025-01-05 21:46:45	2025-01-05 21:46:45
1681	PP-ukysH22Ui4	22.60113035	ITC-1e2g490fDv	2025-01-05 21:46:45	2025-01-05 21:46:45
1682	PP-1UKxB55gNv	33.64262659	ITC-xWYNRrs8yJ	2025-01-05 21:46:45	2025-01-05 21:46:45
1683	PP-CCqe5NJGiH	7.24800889	ITC-5SKQtT3vyf	2025-01-05 21:46:45	2025-01-05 21:46:45
1684	PP-qzno99ycGG	24.83873700	ITC-4lqsfdy1G3	2025-01-05 21:46:45	2025-01-05 21:46:45
1685	PP-ugaWZR7F2H	18.53463612	ITC-BKrKKpzteK	2025-01-05 21:46:45	2025-01-05 21:46:45
1686	PP-TEKXzJDZ9t	17.59978341	ITC-ncLOsPXpV0	2025-01-05 21:46:45	2025-01-05 21:46:45
1687	PP-zQIXtzDBld	27.09679000	ITC-gngFgoaoGi	2025-01-05 21:46:45	2025-01-05 21:46:45
1688	PP-eNvG6kC8s3	27.09679000	ITC-zr840ljEPc	2025-01-05 21:46:45	2025-01-05 21:46:45
1689	PP-Q6fSZqnV0h	39.40094230	ITC-p9IWpWM5vc	2025-01-05 21:46:45	2025-01-05 21:46:45
1690	PP-j4whdNnvA8	19.33939257	ITC-flY9k7HKFl	2025-01-05 21:46:45	2025-01-05 21:46:45
1691	PP-Dq9lYRDhZu	15.27082056	ITC-ft4j6MrmEp	2025-01-05 21:46:45	2025-01-05 21:46:45
1692	PP-kWIVDDRpe2	152.90984779	ITC-lcMghoXuxS	2025-01-05 21:46:45	2025-01-05 21:46:45
1693	PP-kJfwmZxMvo	313.03000477	ITC-NHnbmFh8Ej	2025-01-05 21:46:45	2025-01-05 21:46:45
1694	PP-0skEo28NeJ	41.70756823	ITC-ncZ6SSLhOc	2025-01-05 21:46:45	2025-01-05 21:46:45
1695	PP-hKHn2HZg8y	45.16134000	ITC-3uK8WuMYbf	2025-01-05 21:46:45	2025-01-05 21:46:45
1696	PP-QRd4tiraix	19.70104508	ITC-wi4u5b0jdJ	2025-01-05 21:46:45	2025-01-05 21:46:45
1697	PP-VGyKYMFYXC	4.71911338	ITC-0KZCWKi2eL	2025-01-05 21:46:45	2025-01-05 21:46:45
1698	PP-IAc6x2ClCq	29.80033361	ITC-XbiBgsSJkM	2025-01-05 21:46:45	2025-01-05 21:46:45
1699	PP-Wx8Z9Wf0Vv	2.33772365	ITC-2KcILuMvKT	2025-01-05 21:46:45	2025-01-05 21:46:45
1700	PP-SB7ji4aHUE	13.62563000	ITC-QuCgap9eFI	2025-01-05 21:46:45	2025-01-05 21:46:45
1701	PP-LinqfmfVlT	55.41896028	ITC-G5BiAoEKzU	2025-01-05 21:46:45	2025-01-05 21:46:45
1702	PP-TGVYzLaPqz	31.09411714	ITC-NK4e9qdvdM	2025-01-05 21:46:45	2025-01-05 21:46:45
1703	PP-7bfN7AWS9O	7.83975531	ITC-7rUVFjBAdo	2025-01-05 21:46:45	2025-01-05 21:46:45
1704	PP-hrxNDiPxfF	0.00000000	ITC-wKyyQSfDUp	2025-01-05 21:46:45	2025-01-05 21:46:45
1705	PP-vDGeHIskTO	1.88589667	ITC-mFhh9aMM9V	2025-01-05 21:46:45	2025-01-05 21:46:45
1706	PP-oOVJPJLIYy	0.38635564	ITC-SNfUA4zPX9	2025-01-05 21:46:45	2025-01-05 21:46:45
1707	PP-0H6HKeeUSn	2.25806700	ITC-oa4hGuM7UC	2025-01-05 21:46:45	2025-01-05 21:46:45
1708	PP-JHVVqFWpf8	0.00000000	ITC-JGR3FfLxC6	2025-01-05 21:46:45	2025-01-05 21:46:45
1709	PP-oD502wNaSC	2.72414849	ITC-YsOvuKKHva	2025-01-05 21:46:45	2025-01-05 21:46:45
1710	PP-vKlollPNJE	2.06940094	ITC-stchngBjHP	2025-01-05 21:46:45	2025-01-05 21:46:45
1711	PP-mt5UvwUCbD	0.41335152	ITC-wIwSqATAWE	2025-01-05 21:46:45	2025-01-05 21:46:45
1712	PP-2FrZpeep9w	2.14433800	ITC-ze20g04VB2	2025-01-05 21:46:45	2025-01-05 21:46:45
1713	PP-9Rn0A3vIWW	129.28160260	ITC-QXGzxMvmKL	2025-01-05 21:46:45	2025-01-05 21:46:45
1714	PP-0NeZpSbnAK	2.49023620	ITC-YyyMDt1mKa	2025-01-05 21:46:45	2025-01-05 21:46:45
1715	PP-2nadRsTBS2	2.75420256	ITC-UYLbRC56Pw	2025-01-05 21:46:45	2025-01-05 21:46:45
1716	PP-ZjKSoCzlGV	0.81074713	ITC-hKEcezNxWC	2025-01-05 21:46:45	2025-01-05 21:46:45
1717	PP-5pC7X6uRx4	4.07780756	ITC-Dcs5Y9nS5L	2025-01-05 21:46:45	2025-01-05 21:46:45
1718	PP-HIU1zrw9hc	2.30888160	ITC-tZg0t4X5xN	2025-01-05 21:46:45	2025-01-05 21:46:45
1719	PP-85dXVKGXm2	2.34838968	ITC-G5Ad3PJneB	2025-01-05 21:46:45	2025-01-05 21:46:45
1720	PP-3929oHuCPs	2.26690728	ITC-nhcMdc7OyW	2025-01-05 21:46:45	2025-01-05 21:46:45
1721	PP-c9uiWp2JrV	2.23198779	ITC-m7i1VBxXNm	2025-01-05 21:46:45	2025-01-05 21:46:45
1722	PP-5e6v2KkcEE	19.89965541	ITC-y9rHXdOAhj	2025-01-05 21:46:45	2025-01-05 21:46:45
1723	PP-lH5t3bNhvv	25.99035117	ITC-GvO24AQJZE	2025-01-05 21:46:45	2025-01-05 21:46:45
1724	PP-rCKdnIXQcp	52.34036998	ITC-IKyyNjzZE0	2025-01-05 21:46:45	2025-01-05 21:46:45
1725	PP-SMNbQKhN3T	4.89653125	ITC-f55SXSrqrR	2025-01-05 21:46:45	2025-01-05 21:46:45
1726	PP-b6DxJrnfNU	115.07558804	ITC-Igk7zIiFps	2025-01-05 21:46:45	2025-01-05 21:46:45
1727	PP-pQ0LSWnWHE	1.85161200	ITC-pr2IoJIB0z	2025-01-05 21:46:45	2025-01-05 21:46:45
1728	PP-4jpTeBpSTd	217.85946144	ITC-vAwQY8gH2H	2025-01-05 21:46:45	2025-01-05 21:46:45
1729	PP-jGdfmkkA6T	2.14550749	ITC-0zjnsbw0wu	2025-01-05 21:46:45	2025-01-05 21:46:45
1730	PP-5aFZU7HOgG	29.77360350	ITC-MO95F6ARHa	2025-01-05 21:46:45	2025-01-05 21:46:45
1731	PP-n0GlNwhVEj	29.77360350	ITC-AySSuDO0gi	2025-01-05 21:46:45	2025-01-05 21:46:45
1732	PP-RuWWHh6vFR	27.15375043	ITC-lykjDQVfnF	2025-01-05 21:46:45	2025-01-05 21:46:45
1733	PP-x14Kl8s0jc	51.41618559	ITC-J7EPLe7ruL	2025-01-05 21:46:45	2025-01-05 21:46:45
1734	PP-ogBXfsInTt	3.70322400	ITC-dI9XrtHs6d	2025-01-05 21:46:45	2025-01-05 21:46:45
1735	PP-pedMlUdnx6	1.95549455	ITC-3Rh4ox62hn	2025-01-05 21:46:45	2025-01-05 21:46:45
1736	PP-vU70msUysI	10.13421938	ITC-lvrnrUsdGJ	2025-01-05 21:46:45	2025-01-05 21:46:45
1737	PP-QS36owtKXK	143.97642921	ITC-FjvE5ODTlu	2025-01-05 21:46:45	2025-01-05 21:46:45
1738	PP-1PS76CA2bo	21.72260454	ITC-N6BOPMHJ5z	2025-01-05 21:46:45	2025-01-05 21:46:45
1739	PP-gEXfTgpVoT	3.91650571	ITC-oMN46k7kan	2025-01-05 21:46:45	2025-01-05 21:46:45
1740	PP-JL87jxzleI	1.85161200	ITC-m3MRU2bJ9F	2025-01-05 21:46:45	2025-01-05 21:46:45
1741	PP-bDndvsKOjG	2.03060904	ITC-Z692r80YQL	2025-01-05 21:46:45	2025-01-05 21:46:45
1742	PP-ibmx4fmUTq	2.02991818	ITC-GgvO41uKHy	2025-01-05 21:46:45	2025-01-05 21:46:45
1743	PP-0ykeW9AAKF	1.85161200	ITC-xNQZsYmvwv	2025-01-05 21:46:45	2025-01-05 21:46:45
1744	PP-5hDuWFRHrK	91.74010201	ITC-gKLELcDX6G	2025-01-05 21:46:45	2025-01-05 21:46:45
1745	PP-Ug5DCExKgl	58.43121630	ITC-S6dbIk79cE	2025-01-05 21:46:45	2025-01-05 21:46:45
1746	PP-5px2lvPayH	225.80670000	ITC-uzsAOBkQp1	2025-01-05 21:46:45	2025-01-05 21:46:45
1747	PP-9I2Q7ru4St	18.51612000	ITC-iSV1wESOKD	2025-01-05 21:46:45	2025-01-05 21:46:45
1748	PP-91xrSTkjNV	1.85161200	ITC-urP93T9Md3	2025-01-05 21:46:45	2025-01-05 21:46:45
1749	PP-n3SyTOAVG2	9.90612420	ITC-60gEs0t6rc	2025-01-05 21:46:45	2025-01-05 21:46:45
1750	PP-x8zBC63EUq	5.55483600	ITC-m39FWedy91	2025-01-05 21:46:45	2025-01-05 21:46:45
1751	PP-63HaSEw9DS	1.85161200	ITC-2invasa2qG	2025-01-05 21:46:45	2025-01-05 21:46:45
1752	PP-fTE0HTM2ej	1.85161200	ITC-21gDyB05pj	2025-01-05 21:46:45	2025-01-05 21:46:45
1753	PP-LxALh3dB33	1.85161200	ITC-PRsIBpFHlW	2025-01-05 21:46:45	2025-01-05 21:46:45
\.


--
-- Data for Name: package_reinvests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_reinvests (id, uuid, package_uuid, expire, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: package_withdraws; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_withdraws (id, uuid, package_uuid, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: partners; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.partners (id, user_id, partner_id, created_at, updated_at) FROM stdin;
1	14	4513	2024-09-25 13:10:12	2024-09-25 13:10:12
2	16	4513	2024-09-26 13:08:53	2024-09-26 13:08:53
3	4513	1	2024-10-03 13:02:00	2024-10-03 13:02:00
4	4510	1	2024-10-03 16:16:31	2024-10-03 16:16:31
5	15	4501	2024-10-03 17:17:37	2024-10-03 17:17:37
6	4471	4470	2024-10-03 17:18:27	2024-10-03 17:18:27
7	4509	4471	2024-10-04 11:54:24	2024-10-04 11:54:24
8	4512	4509	2024-10-04 11:54:46	2024-10-04 11:54:46
9	4498	4470	2024-10-04 11:55:23	2024-10-04 11:55:23
10	4501	4470	2024-10-04 11:55:39	2024-10-04 11:55:39
11	17	4505	2024-10-13 15:12:52	2024-10-13 15:12:52
12	4494	4490	2024-10-15 10:23:35	2024-10-15 10:23:35
13	4495	4490	2024-10-15 10:24:20	2024-10-15 10:24:20
14	4497	4490	2024-10-15 10:32:33	2024-10-15 10:32:33
15	4503	4497	2024-10-15 10:33:15	2024-10-15 10:33:15
16	4505	4497	2024-10-15 10:33:34	2024-10-15 10:33:34
17	4508	4503	2024-10-15 10:33:49	2024-10-15 10:33:49
18	18	4490	2024-10-17 12:26:57	2024-10-17 12:26:57
19	19	4490	2024-10-18 11:41:25	2024-10-18 11:41:25
20	4459	4464	2024-10-22 11:36:51	2024-10-22 11:36:51
21	3	4464	2024-10-22 11:37:18	2024-10-22 11:37:18
22	20	4505	2024-10-23 11:05:26	2024-10-23 11:05:26
23	21	4470	2024-10-25 08:57:03	2024-10-25 08:57:03
24	23	22	2024-11-05 18:14:32	2024-11-05 18:14:32
25	22	4490	2024-11-06 11:55:24	2024-11-06 11:55:24
26	35	34	2024-11-12 13:02:31	2024-11-12 13:02:31
27	34	4470	2024-11-12 13:02:59	2024-11-12 13:02:59
28	36	4490	2024-11-17 12:08:14	2024-11-17 12:08:14
29	37	20	2024-11-20 16:54:05	2024-11-20 16:54:05
30	38	4505	2024-11-21 12:34:39	2024-11-21 12:34:39
31	39	20	2024-11-23 12:24:23	2024-11-23 12:24:23
32	43	4513	2024-11-30 14:04:48	2024-11-30 14:04:48
33	44	4513	2024-11-30 21:45:21	2024-11-30 21:45:21
34	45	4501	2024-12-09 16:02:53	2024-12-09 16:02:53
35	52	8	2024-12-29 16:19:27	2024-12-29 16:19:27
36	53	38	2024-12-29 18:43:34	2024-12-29 18:43:34
37	54	53	2024-12-29 19:00:16	2024-12-29 19:00:16
38	55	53	2024-12-29 19:15:25	2024-12-29 19:15:25
39	57	53	2024-12-30 10:40:40	2024-12-30 10:40:40
40	59	53	2025-01-02 17:27:50	2025-01-02 17:27:50
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
TbFoOv7NFR0LqkTHrmJkr7uhfhB9Vb7Jfg2OcYhU	4510	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiOEVrRk5lRzJPRTNXbHo2RXdpOHdpQkNkTGQ2dm94eDIxVUlaQ3UzTiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6Mjk6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUxMDt9	1736196154
PPU8jGrNVW8k4pGYAGgCS6WIUc0qHrXqKWimNDDv	4504	195.161.62.134	Mozilla/5.0 (Linux; Android 10; CDY-NX9A; HMSCore 6.14.0.322) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.5735.196 HuaweiBrowser/15.0.4.312 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRm5kZVdJQ1JVbElDNkgyczFGaEZhYVRoVjdIc2NyRjY2NDVtemFrRyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUwNDtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736195362
7G4uZihKUslp6PwHBkDBTlehJ4la1CymrfdpAfJA	\N	195.161.62.134	Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)	YTozOntzOjY6Il90b2tlbiI7czo0MDoiNllCcVJpZkE0Z29Gd3JSTmtxRmF1SEFvekc5NkxZZG1zSXZpdHpGYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736203850
739WpSQh5d7Cl4JlPtcG2XdWXmzR7GHa0i724Wj4	\N	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiaExOSUwwUFkwRm1uSTdLMjBpQkM2QTlRZ3prSFppM0dUTjBkTzFQNSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736224676
tsIBkQRknAQ3YgZBoMpsCuDGCl1KcOM0pRZbFUZt	4504	195.161.62.134	Mozilla/5.0 (Linux; Android 10; CDY-NX9A; HMSCore 6.14.0.322) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.5735.196 HuaweiBrowser/15.0.4.312 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTjJzTm5YZ3BtSGhRSnppUE1GalBNZk93Y2pkV2Q5amN2b25CclZWciI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUwNDtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736233404
smjyjCawksHbD29tSM1lIwDdhsTZR7sYxveWoTRo	1	195.161.62.134	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTo2OntzOjY6Il90b2tlbiI7czo0MDoic1U2VXVzbHZ0Z2VYVVlNZ0NoZXBDVFBhRVVUZ2o0RUw3MDJlNG9xMSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzI6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hZG1pbi9yZXNvdXJjZS9kZXBvc2l0LXJlc291cmNlL2RlcG9zaXQtaW5kZXgtcGFnZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjA6e31zOjU2OiJsb2dpbl9tb29uc2hpbmVfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MjM6InBhc3N3b3JkX2hhc2hfbW9vbnNoaW5lIjtzOjYwOiIkMnkkMTIkUUtPNXQvVWhaMzNNUHBQQmhOdDluZW0vNUlZM1lTVy92endraWxRMFlPSzVtVU1OcTlnSS4iO30=	1736252300
8j9lwNDZSdnvDSlyqbcBrDeQpWJcYGvJq3sMDrxF	4464	195.161.62.134	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTo3OntzOjY6Il90b2tlbiI7czo0MDoidXpudnB1Zk9BeEdLRU4xSTVmSXUxb1ZLaUEyZ1dtVGMwR3M4Y0ZHaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L215LWJ1c2luZXNzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyYiQwNyRyVG1QSEQ3RFJQbzRjeHQxajVBaGZPVjhZLzk3MnZLYm9rbUk4NTdiMS5KWG9UbzV4c2c4MiI7czo1NjoibG9naW5fbW9vbnNoaW5lXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjIzOiJwYXNzd29yZF9oYXNoX21vb25zaGluZSI7czo2MDoiJDJ5JDEyJFFLTzV0L1VoWjMzTVBwUEJoTnQ5bmVtLzVJWTNZU1cvdnp3a2lsUTBZT0s1bVVNTnE5Z0kuIjtzOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0NDY0O30=	1736238915
oEUEeT4TjskIJ58X48O4U1U6M47f7NeoM83hOwR9	61	195.161.62.134	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTldJRU5QSFlwdzFwUHJtQXpid1YwWWZ2YW9nQWdvU29zcjY1YXlDWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L215LWJ1c2luZXNzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjE7fQ==	1736238916
kFgkghbSHeq38e6D1kePATnE4Iokm7gdUbqqMo2e	\N	195.161.62.134		YTozOntzOjY6Il90b2tlbiI7czo0MDoieUZwR3RiZEVDa2l2TGpubDA3eEgwakRmU21TWVUwTHUycG1uMkw4VSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736204420
jO0EXhytVmvohBqrGpCoodsHHZd5cQrlK7HEJt7X	\N	195.161.62.134	Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)	YTozOntzOjY6Il90b2tlbiI7czo0MDoidm0xOE9WcVk5WGw2STN6aXVKTkJPT2dBcE4zaEhYR0pKa0tNeVdoTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736191258
kwaZFMALSbjHkLB85xI3LXhKXBL0nHP9oaEuPyY7	\N	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiNnd6emtxejZVWlRsZ3Z2ZlkwNldJNUVodHR1UFhjOGpYalZYOWpySCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736195818
GQSxqwqDRXjHN2fksCElthXCRdaCsA65Rq5rsnYX	\N	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 18_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/131.0.6778.154 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU09TM05tQ0VubmN5TktyR09JcnRGcm1xOFJlcml4NWFMcm5NNW1yYiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736247367
JennyUtarc2lmlruy4bUc6tLInRca19Afq0bm1P1	16	195.161.62.134	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoieHN4b0oyNG00OEtwQ2pUR2I0N3FBbzlWQ3hWb3hEQlVJVXZTQlVlbCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTY7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736230533
ncnur4Dx5ap3mrKSn8bmLL7BZhTgMVuTXb1NNLlR	\N	195.161.62.134	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 YaBrowser/24.10.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoickhMU3NYQnlxeVJrbUFJQkx3U2pYQ2M1cjZRU3lSM3UzMzVSeU83ayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736234664
jbPQxJg0hMQEPEnPtQPvXYlJj0jMRVPbYUJtgMMf	4464	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRDFSZzBJRmdpa0QxUXVEOVdzcWtNb1RKWDJPcG45VjlKNHFLZUpMSyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ0NjQ7fQ==	1736196206
y1gTrViRQ5VLm6ymbLdVQotPlEj4Dr9vZoj7YNma	50	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiT2dYQkFuMGdybzI3aHZRU1EzanpVNEhJc1lVcDBYZ1pxdFRET1doWSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTA7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736206432
kO1lKmVF88fUuG7sbjzA3NYbYC0203c6KThhEZNb	\N	195.161.62.134	Mozilla/5.0 (Linux; Android 12; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.6778.135 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMXFBY1dRNHlDU1Q4bkZDQWdNaUcyd0hKODVJdEZ0U0VDTmx2WTIxNSI7czo3OiJwYXJ0bmVyIjtzOjEwOiJNSUtIQUlMLTYxIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo0MToiaHR0cHM6Ly9pdGNhcGl0YWwudG9wLz9wYXJ0bmVyPU1JS0hBSUwtNjEiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736243467
04oTnMROoOvEcolCR2GPhxuso4w0aLYNl7qNTWDm	\N	195.161.62.134	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3gxeEdCWDBwd0Y5YkdadlUxaGVGOEJ1WG9nY0ljaENWYW1IbDBIWCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9zaWduLXVwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736230893
aGdPSGgSdAeSTNByhG3irIEDWwMYsE7x9rT7ckov	\N	195.161.62.134	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoickNOWERnZ2ZGN21memRaSURmcnA3aTdjY21kUllSUDZ2cGkxaWV4NSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736191342
QFvsLbhYSmmvPpxvWGgHJZLDpYMWPmSCr2hwimAO	\N	195.161.62.134	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiQzYxeUp3SDAxcEt0aGdJT0RUQWlkY29tSW45Rmt4UGN2dWhjcEpteiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736235398
AVUVU4J4QB8TgikLoVuBqsT0FPofMz2B1rj76iHI	50	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYlJobGplMjVkTjZNZGtNWlp4a04yTHRnbmp0bnhuSXhaYW90cGJhSiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTA7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736196729
rsurzac3CI0NxO4AmcAdd3rwwxbQctDAV0TnGwf8	\N	195.161.62.134	Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)	YTozOntzOjY6Il90b2tlbiI7czo0MDoiZW81U1RNMEVqMWVyWmZqMEhOQk5rTDQwQUlIRFlYQnNKVTJaQjQ2dyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9zaWduLXVwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736218915
Ndtt3icM4RXx2GBlRTwhq1x83JKQ5hIc6w7JqHX8	52	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSG8xYmZTV2V6MFo2Q216N09ZdVE1WUVBNjNDRlVuSVJ1bloxbjJuUiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736193167
AIptIKnQWctxUJUgYS97SGYTICUcKaV8HfpYQM2n	4513	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 16_7_10 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWFh1Nk5DczQ2aW9XY1VVVGJOb3pxVGdaYVJPWUthZlBWdmhSVUhLRSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUxMztzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736231379
ttLrir8ikCIu4FmjfcP1wzTzzq8V7tfKVKJ67KlV	39	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoieVc4ZHZNVUZLenFrWEpYQWRxZFlhdGhLdFgzOG9oaWFrZUxkMVloaSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzk7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736236117
3zfBmQ8F1BAr8I6NCJYiPhiIlukn4TnPlYsteZSs	50	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoicXUzQkN1dTdTRDc5Z1d4UnQwRnV2bmx6NFg3bEpSZlFrQVNrU3VWMCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTA7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736238533
IOucFw05wr4u8yHolFdBdWN1UZwQsfSswA6G0lyO	4470	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/27.0 Chrome/125.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTTkzTzdqSUJya0pKT0tIa1dxMlBFMDNwejdlTkJNcWFwV0JMdmFkciI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDQ3MDtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyOToiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736197911
yyzQPPRPpOLRUuU0NwzvKMzGzLWHxhQfffwvrGqn	4471	195.161.62.134	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUmpOcnRlUlRkd1FYalQ1ajFtV1NoY0VsUDdKdXB0WFk4OFNaWFF2eiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50L2l0YyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ0NzE7fQ==	1736247160
jzfMdMOj00MGHI8cSOBpstOJpqgBhGARkxwm4U65	4500	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 18_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1.1 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiREpSNTVqQ3ptZzBHY1N0WGRQa2h2eDZtbnUycXdrTDMwR25CUm1CUCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUwMDtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozMzoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQvaXRjIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736222374
tLugafqBtnMTW4Dp7On47Hr5LPu6sCrzA4vEVut0	1	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 18_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/131.0.6778.154 Mobile/15E148 Safari/604.1	YTo1OntzOjY6Il90b2tlbiI7czo0MDoia0lJOTVUYW1vSWpKV1NNeDRid01EQllkYlVTUHNjVjNpc0pTdThORyI7czo1NjoibG9naW5fbW9vbnNoaW5lXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjIzOiJwYXNzd29yZF9oYXNoX21vb25zaGluZSI7czo2MDoiJDJ5JDEyJFFLTzV0L1VoWjMzTVBwUEJoTnQ5bmVtLzVJWTNZU1cvdnp3a2lsUTBZT0s1bVVNTnE5Z0kuIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo3NDoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FkbWluL3Jlc291cmNlL3dpdGhkcmF3LXJlc291cmNlL3dpdGhkcmF3LWluZGV4LXBhZ2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736194643
mWgdzlXanVRZO7AnBuASDt8hUtQSDgfSz6odEuyd	4485	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoieHAyVWpRNWRTeHk3R0NDTmFscTZNYkxhdzUwbHFxY3lMRzFIanpoMCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDQ4NTtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyOToiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736231315
DcuCB1xx9r7ZpTmyHFVIqUKCcA9Rx0xO3tlkAw42	4503	195.161.62.134	Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.118 Mobile Safari/537.36 XiaoMi/MiuiBrowser/14.25.0-gn	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiaGUzQzVwbzJubWJxUUdxa3lpRGxOYlV2cWxPNWZySGdISjQ1a2JveCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDUwMztzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyOToiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FjY291bnQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736203213
HzWduvBnOSttHAhExUnKXLQ39gqZmEZEb3spfHJn	34	195.161.62.134	Mozilla/5.0 (Linux; arm_64; Android 14; TECNO LH7n) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.6723.100 YaBrowser/24.12.3.100.00 SA/3 Mobile Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSDRtZEYxRGlLMjhUVExhUmVhdlZyS1IwaVduOXJVZW9HSjI5dDNCUyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MzQ7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjk6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcC9hY2NvdW50Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1736222670
DeqzIbgGeTD5aS61omx3oD0y4lScTrZskOEP3O15	\N	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWxKbXFrR3c3eHVBMFhhQlp5NjZzc01Cd2dVNmVGZXZaeUpoa29qUiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736233239
qGWBkJ32eJJee3R9frat5AhIQ2OjvBnqKNAvHIN4	1	195.161.62.134	Mozilla/5.0 (iPhone; CPU iPhone OS 18_2_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/131.0.6778.154 Mobile/15E148 Safari/604.1	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiVElJV2VwUVdITFI1aGZ0bExacFJ1eEJpYUMyZ2o4ZlMybzVuU2E5ZSI7czo1NjoibG9naW5fbW9vbnNoaW5lXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjIzOiJwYXNzd29yZF9oYXNoX21vb25zaGluZSI7czo2MDoiJDJ5JDEyJFFLTzV0L1VoWjMzTVBwUEJoTnQ5bmVtLzVJWTNZU1cvdnp3a2lsUTBZT0s1bVVNTnE5Z0kuIjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo3NDoiaHR0cHM6Ly9pdGNhcGl0YWwudG9wL2FkbWluL3Jlc291cmNlL3dpdGhkcmF3LXJlc291cmNlL3dpdGhkcmF3LWluZGV4LXBhZ2UiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19	1736194644
TQAptNyEY1FSMDLwJmVsJbKtOAXEtBRIn25JJv9d	\N	195.161.62.134	Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36; 360Spider	YTozOntzOjY6Il90b2tlbiI7czo0MDoibkFrbjlzTnJuRFJyTnNGdTg1cFRLVUFOa3JqV00xVlVmNEFLMGhmUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHBzOi8vaXRjYXBpdGFsLnRvcCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1736196029
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (id, uuid, amount, user_id, balance_type, trx_type, accepted_at, rejected_at, created_at, updated_at) FROM stdin;
1	HD-Ug9b7G3WXi	2015.43440000	4464	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
2	ITC-GQFm3SnCSL	1000.00000000	4464	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
3	ITC-e8x2GsZeYs	1011.00000000	4464	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
4	HD-LbmmliiK2G	0.00000000	4486	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
5	HD-RFEHV2AGwt	0.00000000	4487	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
6	HD-Q1zUeWWGwG	0.00000000	4488	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
7	HD-5y5BWwMdpO	0.00000000	4489	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
8	HD-GCU3AVZEvh	0.00000000	4451	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
9	HD-U41186zQxM	0.00000000	4465	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
13	HD-K7ShRJKLs0	6620.80330000	4452	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
14	ITC-luUiKG6k2m	1000.00000000	4452	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
15	ITC-8zlKdVTEKb	1500.00000000	4452	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
16	ITC-Z0CMEPUbwC	1000.00000000	4452	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
17	ITC-5Z9isUEMMA	500.00000000	4452	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
18	HD-rDudg3WI5O	0.00000000	4453	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
19	HD-bbfk9NO3Dn	0.00000000	4467	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
20	HD-ALekWTgaDa	800.00000000	4468	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
21	ITC-FX6dgjpbdM	800.00000000	4468	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
22	HD-KxWhBcn7cd	0.00000000	4482	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
23	HD-5PbsPJHBga	0.00000000	4469	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
24	HD-6wARLwfp1Z	0.00000000	4483	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
25	HD-o6Q9ix0ubp	0.00000000	4484	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
26	HD-VcG2IcJ2QC	0.00000000	4456	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
27	HD-1KJ4E2z9UD	0.00000000	4472	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
28	HD-KuT2BTo3Yf	0.00000000	4477	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
29	HD-jXqaQhpJAz	0.00000000	4457	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
30	HD-kvCZuxN84w	0.00000000	4458	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
31	HD-4KaGWiY5n2	0.00000000	4473	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
32	HD-DzHXS73bWD	0.00000000	4474	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
33	HD-fKUAPDySSV	0.00000000	4475	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
34	HD-wkbHdfD9h7	0.00000000	4476	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
35	HD-W4cCOHB1PE	500.64900000	4459	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
36	ITC-FIuyoYe0ta	500.00000000	4459	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
37	HD-fckM6OLcJU	700.00000000	4460	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
38	ITC-lALXYPYWQ7	700.00000000	4460	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
39	HD-2DjN6dHc6a	0.00000000	4461	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
40	HD-XBDUFBSvtZ	0.00000000	4463	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
41	HD-8seKEnsEPi	1423.37530000	4462	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
42	ITC-dHT7IzMEbg	1000.00000000	4462	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
43	ITC-QRO4BEQ6wZ	400.00000000	4462	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
44	HD-E0ra9KKqMq	0.00000000	4448	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
45	HD-f5rmMj4tc3	0.00000000	4438	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
46	HD-0EZcrtm4N4	0.00000000	4439	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
47	HD-RdzCX5n9QU	0.00000000	4440	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
48	HD-fdhQpO3khv	0.00000000	4441	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
49	HD-Ym9qPHNR1M	3.00000000	4442	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
50	HD-RqTG2tGkSb	2.00000000	4443	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
51	HD-Pxs9tXRkFm	0.00000000	4444	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
52	HD-CO1ltz49q9	0.00000000	4445	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
53	HD-h9RXeFTXFA	0.00000000	4446	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
54	HD-AOR7QYQFQy	2.00000000	4447	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
55	HD-C0LVapDXtL	0.00000000	4449	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
56	HD-bh5WW6ukeD	973.00450000	4485	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
57	ITC-WHqNMXYAxy	623.00000000	4485	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
58	ITC-7ApObtH2pi	350.00000000	4485	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
59	HD-wlndx5pfHa	422.00000000	4470	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
61	HD-n4mRS2BQtf	4193.40760000	4471	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
62	ITC-XKDP9jfHOn	3000.00000000	4471	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
63	ITC-D7q0DLwZ4y	1100.00000000	4471	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
64	HD-kEFwkuwolr	0.00000000	4491	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
65	HD-TRokVgRLw0	0.00000000	4492	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
12	ITC-IzyqsIsBho	800.00000000	4466	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
60	ITC-lWkgBm6M8y	370.00000000	4470	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-12-22 07:46:26
66	HD-RF5ZFOUhD9	273.00000000	4493	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
67	ITC-MmPRFIosoQ	273.00000000	4493	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
68	HD-8X1Cc06ssg	232.41960000	4495	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
69	ITC-1h9KUgir50	213.00000000	4495	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
70	HD-5aFYjhb6if	1064.04920000	4494	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
71	ITC-1DHfDk80aX	504.00000000	4494	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
72	ITC-DlaAptpq2c	500.00000000	4494	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
73	HD-rhmf87uzlh	2094.43110000	4490	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
74	ITC-1e2g490fDv	789.00000000	4490	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
75	ITC-xWYNRrs8yJ	1185.00000000	4490	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
76	HD-CVyA28M9Qb	0.00000000	4496	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
77	HD-wM3E3SCiKd	300.04800000	4498	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
78	ITC-5SKQtT3vyf	300.00000000	4498	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
79	HD-PVWrFHcd2q	0.66510000	4450	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
80	HD-dMVf1Yx1Sa	1100.41000000	4500	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
81	ITC-4lqsfdy1G3	1100.00000000	4500	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
82	HD-J7rtmNgE1q	0.00000000	4502	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
83	HD-tY1E7PsGMm	1001.60040000	4503	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
84	ITC-BKrKKpzteK	1001.00000000	4503	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
85	HD-OPor56gpKf	550.40000000	4504	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
86	ITC-ncLOsPXpV0	550.00000000	4504	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
87	HD-PengmCmEy3	1000.60000000	4505	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
88	ITC-gngFgoaoGi	1000.00000000	4505	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
89	HD-t1BhEempdF	0.00000000	4506	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
90	HD-49nS0BNFX1	0.00000000	4507	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
91	HD-uTuBY6eUyw	1000.00000000	4508	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
92	ITC-zr840ljEPc	1000.00000000	4508	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
93	HD-gexFFqZBfR	1025.00000000	4509	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
95	HD-woGcDDgixk	500.00000000	4510	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
96	ITC-flY9k7HKFl	500.00000000	4510	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
97	HD-vhC7GBcZ28	0.00000000	4511	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
98	HD-eLm4VhYGDH	16217.79950000	4501	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
99	ITC-ft4j6MrmEp	505.00000000	4501	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
100	ITC-lcMghoXuxS	5353.00000000	4501	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
101	ITC-NHnbmFh8Ej	10359.00000000	4501	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
102	HD-aF7EkYEHuP	1166.00000000	4512	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
104	HD-eFVzkfi5AO	2000.92000000	4497	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
105	ITC-3uK8WuMYbf	2000.00000000	4497	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
106	HD-xaHGB9Mq7H	550.00000000	4513	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
109	WPP-n73tRFnBz7	33.01360000	4464	main	withdraw_package_profit	2024-08-25 16:53:30	\N	2024-08-25 16:53:30	2024-08-25 16:53:30
110	WPP-AM8lbnMFAd	27.19550000	4464	main	withdraw_package_profit	2024-08-25 16:53:39	\N	2024-08-25 16:53:39	2024-08-25 16:53:39
111	WPP-yVb1LoBfSZ	28.89120000	4490	main	withdraw_package_profit	2024-08-25 18:04:21	\N	2024-08-25 18:04:21	2024-08-25 18:04:21
112	WPP-tUyflC07qQ	15.10990000	4494	main	withdraw_package_profit	2024-08-25 18:11:42	\N	2024-08-25 18:11:42	2024-08-25 18:11:42
119	DP-PXV8wIfaTu	200.00000000	3	main	deposit	2024-08-26 10:44:00	\N	2024-08-26 10:37:34	2024-08-26 10:37:34
10	HD-5gL51GKkcT	886.08000000	4466	main	hidden_deposit	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-08-25 13:02:04
121	HD-Wjsd234kj	6.00000000	4464	main	hidden_deposit	2024-08-28 13:00:00	\N	\N	\N
126	WPP-dHF5FA87RS	84.06256500	4459	main	withdraw_package_profit	2024-08-29 17:01:54	\N	2024-08-29 17:01:54	2024-08-29 17:01:54
128	WPP-aCCY0GdWJJ	9.66232155	4501	main	withdraw_package_profit	2024-08-30 23:01:37	\N	2024-08-30 23:01:37	2024-08-30 23:01:37
131	DP-LUlGuyBMNC	1000.00000000	4485	main	deposit	\N	2024-09-12 09:42:21	2024-08-31 10:11:40	2024-09-12 09:42:21
130	DP-ISkgj96yrq	1000.00000000	4485	main	deposit	\N	2024-09-12 09:42:22	2024-08-31 10:11:36	2024-09-12 09:42:22
129	DP-B3wc3WDiis	1000.00000000	4485	main	deposit	\N	2024-09-12 09:42:23	2024-08-31 10:11:32	2024-09-12 09:42:23
125	DP-fEjk9345LA	194.00000000	4466	main	deposit	\N	2024-09-12 09:42:28	2024-08-29 04:16:44	2024-09-12 09:42:28
124	DP-P5giw3e75U	194.00000000	4466	main	deposit	\N	2024-09-12 09:42:29	2024-08-29 04:15:47	2024-09-12 09:42:29
123	DP-hSICZmvz3P	194.00000000	4466	main	deposit	\N	2024-09-12 09:42:30	2024-08-29 04:15:35	2024-09-12 09:42:30
118	DP-sao3osLhmC	200.00000000	3	main	deposit	\N	2024-09-12 09:42:36	2024-08-26 10:37:33	2024-09-12 09:42:36
117	DP-XfvvyhjlFL	200.00000000	3	main	deposit	\N	2024-09-12 09:42:37	2024-08-26 10:37:32	2024-09-12 09:42:37
116	DP-v5MLlCIsoD	200.00000000	3	main	deposit	\N	2024-09-12 09:42:38	2024-08-26 10:37:18	2024-09-12 09:42:38
115	DP-etX3QE3D9u	200.00000000	3	main	deposit	\N	2024-09-12 09:42:40	2024-08-26 10:36:49	2024-09-12 09:42:40
114	DP-vRtl5POTLK	200.00000000	3	main	deposit	\N	2024-09-12 09:42:42	2024-08-26 10:36:44	2024-09-12 09:42:42
113	DP-HpKopqktI2	100.00000000	1	main	deposit	\N	2024-09-12 09:42:50	2024-08-26 07:40:34	2024-09-12 09:42:50
108	DP-rtSkVjRrnp	1000.00000000	2	main	deposit	\N	2024-09-12 09:42:51	2024-08-25 14:23:35	2024-09-12 09:42:51
120	ITC-0KZCWKi2eL	207.00000000	3	main	buy_package	2024-08-26 10:58:06	\N	2024-08-26 10:58:06	2024-09-12 10:34:14
94	ITC-p9IWpWM5vc	1025.00000000	4509	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-10-15 16:57:59
107	ITC-wi4u5b0jdJ	563.00000000	4513	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-10-21 11:53:22
132	DP-0JySoPIycL	1000.00000000	4485	main	deposit	2024-08-31 10:22:00	\N	2024-08-31 10:11:42	2024-08-31 10:11:42
134	WPP-M57MSehnt2	8.19999000	4459	main	withdraw_package_profit	2024-09-01 05:34:58	\N	2024-09-01 05:34:58	2024-09-01 05:34:58
135	WPP-RwmxnFfj1U	21.04664100	4500	main	withdraw_package_profit	2024-09-01 06:11:32	\N	2024-09-01 06:11:32	2024-09-01 06:11:32
136	WPP-6gI7j1K5sl	36.39405213	4485	main	withdraw_package_profit	2024-09-01 09:25:17	\N	2024-09-01 09:25:17	2024-09-01 09:25:17
137	WPP-rNlpKEhStb	20.79735850	4485	main	withdraw_package_profit	2024-09-01 09:25:19	\N	2024-09-01 09:25:19	2024-09-01 09:25:19
139	DP-pqW4trsDxB	100.00000000	5	main	deposit	2024-09-01 15:50:00	\N	2024-09-01 15:45:04	2024-09-01 15:45:04
142	WPP-HbYFGpbuOO	15.09618159	4490	main	withdraw_package_profit	2024-09-01 17:33:29	\N	2024-09-01 17:33:29	2024-09-01 17:33:29
143	WPP-YaEm0QAbIU	22.67297235	4490	main	withdraw_package_profit	2024-09-01 17:33:38	\N	2024-09-01 17:33:38	2024-09-01 17:33:38
144	WPP-phCVlcw6KS	9.64318824	4494	main	withdraw_package_profit	2024-09-01 17:35:17	\N	2024-09-01 17:35:17	2024-09-01 17:35:17
145	WPP-8d5VYcSK5i	9.56665500	4494	main	withdraw_package_profit	2024-09-01 17:35:33	\N	2024-09-01 17:35:33	2024-09-01 17:35:33
148	WPP-AS2d2vzedO	102.42060843	4501	main	withdraw_package_profit	2024-09-01 19:12:08	\N	2024-09-01 19:12:08	2024-09-01 19:12:08
149	WPP-hvZ8m2YaUq	198.20195829	4501	main	withdraw_package_profit	2024-09-01 19:12:28	\N	2024-09-01 19:12:28	2024-09-01 19:12:28
150	HD-Gsfj9lmnb	3.00000000	4501	partner	hidden_deposit	2024-09-02 10:00:00	\N	\N	\N
147	WP-BWURWcz6qT	94.00000000	4494	main	withdraw	2024-09-02 16:11:09	\N	2024-09-01 17:41:31	2024-09-02 16:11:09
146	WP-GdOuPG7kqC	187.00000000	4490	main	withdraw	2024-09-02 16:11:12	\N	2024-09-01 17:39:46	2024-09-02 16:11:12
151	WPP-Tu36fYHgZs	14.83000000	4501	main	withdraw_package_profit	2024-09-02 17:36:56	\N	2024-09-02 17:36:56	2024-09-02 17:36:56
152	WPP-8pmAf6xmQi	46.01000000	4501	main	withdraw_package_profit	2024-09-02 17:36:59	\N	2024-09-02 17:36:59	2024-09-02 17:36:59
153	WPP-p9puLjuVRS	105.67000000	4501	main	withdraw_package_profit	2024-09-02 17:37:00	\N	2024-09-02 17:37:00	2024-09-02 17:37:00
138	WP-Lxuy7LXZdC	92.00000000	4459	main	withdraw	2024-09-02 18:33:40	\N	2024-09-01 12:28:29	2024-09-02 18:33:40
154	ITC-QuCgap9eFI	477.00000000	4501	main	buy_package	2024-09-03 15:43:20	\N	2024-09-03 15:43:20	2024-09-03 15:43:20
155	WPP-crCPDvJbUj	10.52332050	4504	main	withdraw_package_profit	2024-09-03 17:38:24	\N	2024-09-03 17:38:24	2024-09-03 17:38:24
156	WPP-vk7loKsesE	7.52988600	3	main	withdraw_package_profit	2024-09-06 05:55:16	\N	2024-09-06 05:55:16	2024-09-06 05:55:16
157	WPP-rJiIPTbuRF	12.67021889	4501	main	withdraw_package_profit	2024-09-08 06:14:36	\N	2024-09-08 06:14:36	2024-09-08 06:14:36
158	WPP-CcSxPVeVfw	126.86949166	4501	main	withdraw_package_profit	2024-09-08 06:14:40	\N	2024-09-08 06:14:40	2024-09-08 06:14:40
159	WPP-gYuojIz0Kj	259.72138586	4501	main	withdraw_package_profit	2024-09-08 06:14:44	\N	2024-09-08 06:14:44	2024-09-08 06:14:44
160	WPP-7NMsrs2cOz	11.30520223	4501	main	withdraw_package_profit	2024-09-08 06:14:48	\N	2024-09-08 06:14:48	2024-09-08 06:14:48
127	DP-J8DIYZ6qQz	2125.00000000	4459	main	deposit	2024-09-08 10:50:41	\N	2024-08-30 13:15:54	2024-09-08 10:50:41
162	WPP-J9Pj6ehWVt	23.72210875	4490	main	withdraw_package_profit	2024-09-08 13:16:57	\N	2024-09-08 13:16:57	2024-09-08 13:16:57
163	WPP-sozvUm8gN3	29.42605254	4490	main	withdraw_package_profit	2024-09-08 13:17:19	\N	2024-09-08 13:17:19	2024-09-08 13:17:19
164	WPP-uXpJT2xttj	14.63365996	4494	main	withdraw_package_profit	2024-09-08 13:18:37	\N	2024-09-08 13:18:37	2024-09-08 13:18:37
165	WPP-bVo9gT19f7	12.41605803	4494	main	withdraw_package_profit	2024-09-08 13:18:39	\N	2024-09-08 13:18:39	2024-09-08 13:18:39
166	WPP-mz6ehE2t0h	28.47589201	4464	main	withdraw_package_profit	2024-09-08 14:57:24	\N	2024-09-08 14:57:24	2024-09-08 14:57:24
167	WPP-zJraM77382	28.04097747	4464	main	withdraw_package_profit	2024-09-08 14:57:26	\N	2024-09-08 14:57:26	2024-09-08 14:57:26
169	DP-ZPAZCHj0DM	400.00000000	5	main	deposit	2024-09-08 15:17:36	\N	2024-09-08 15:09:54	2024-09-08 15:17:36
172	WPP-4HOLYmxnNv	15.80735562	4504	main	withdraw_package_profit	2024-09-08 16:31:48	\N	2024-09-08 16:31:48	2024-09-08 16:31:48
175	DP-cHjW7gROoG	1075.00000000	8	main	deposit	2024-09-08 17:25:36	\N	2024-09-08 17:23:56	2024-09-08 17:25:36
168	WP-opUoi7Bem6	125.00000000	4464	main	withdraw	2024-09-08 18:26:42	\N	2024-09-08 15:06:18	2024-09-08 18:26:42
170	WP-A6IZuYZJ5D	20.00000000	4500	main	withdraw	2024-09-08 18:26:49	\N	2024-09-08 15:19:04	2024-09-08 18:26:49
171	WP-eXMr0XsuIa	411.00000000	4501	main	withdraw	2024-09-08 18:29:49	\N	2024-09-08 16:03:17	2024-09-08 18:29:49
177	ITC-7rUVFjBAdo	400.00000000	5	main	buy_package	2024-09-08 20:41:07	\N	2024-09-08 20:41:07	2024-09-08 20:41:07
178	HD-yRixLX2rie	12.00000000	4501	partner	hidden_deposit	2024-09-09 11:35:17	\N	2024-09-09 11:35:17	2024-09-09 11:35:17
179	HD-85HSNcdBER	120.00000000	4464	partner	hidden_deposit	2024-09-09 11:36:07	\N	2024-09-09 11:36:07	2024-09-09 11:36:07
180	HD-5HuqRAhFxu	3.00000000	4470	partner	hidden_deposit	2024-09-09 11:38:27	\N	2024-09-09 11:38:27	2024-09-09 11:38:27
174	DP-FKAUaYqxBp	1075.00000000	8	main	deposit	\N	2024-09-12 09:41:57	2024-09-08 17:23:46	2024-09-12 09:41:57
173	DP-XLUdUcD5Cl	1075.00000000	8	main	deposit	\N	2024-09-12 09:42:00	2024-09-08 17:23:42	2024-09-12 09:42:00
140	DP-04PABclZMQ	100.00000000	5	main	deposit	\N	2024-09-12 09:42:16	2024-09-01 15:45:10	2024-09-12 09:42:16
122	DP-rKPiZknwt7	194.00000000	4466	main	deposit	\N	2024-09-12 09:42:31	2024-08-29 04:15:15	2024-09-12 09:42:31
161	ITC-G5BiAoEKzU	2230.00000000	4459	main	buy_package	2024-09-08 10:51:23	\N	2024-09-08 10:51:23	2024-11-10 07:34:26
189	ITC-wKyyQSfDUp	0.00000000	4459	main	buy_package	2024-09-12 10:48:18	\N	2024-09-12 10:48:18	2024-12-23 07:33:19
181	HD-jMbwZqWcha	315.00000000	4459	main	hidden_deposit	2024-09-12 10:19:08	\N	2024-09-12 10:19:08	2024-09-12 10:19:08
182	HD-6PKlwFhp4z	150.00000000	8	main	hidden_deposit	2024-09-12 10:28:36	\N	2024-09-12 10:28:36	2024-09-12 10:28:36
183	HD-BjVWNfJVKf	150.00000000	4485	main	hidden_deposit	2024-09-12 10:29:57	\N	2024-09-12 10:29:57	2024-09-12 10:29:57
184	HD-EuIRZSJlNr	150.00000000	4485	main	hidden_deposit	2024-09-12 10:30:06	\N	2024-09-12 10:30:06	2024-09-12 10:30:06
185	HD-r7J4O6jtXF	150.00000000	4459	main	hidden_deposit	2024-09-12 10:31:57	\N	2024-09-12 10:31:57	2024-09-12 10:31:57
186	HD-BVrtsza28L	40.00000000	4459	main	hidden_deposit	2024-09-12 10:32:11	\N	2024-09-12 10:32:11	2024-09-12 10:32:11
187	HD-P4fUg5mb6V	50.00000000	4459	main	hidden_deposit	2024-09-12 10:33:24	\N	2024-09-12 10:33:24	2024-09-12 10:33:24
141	ITC-2KcILuMvKT	115.00000000	5	main	buy_package	2024-09-01 16:10:05	\N	2024-09-01 16:10:05	2024-09-12 10:38:46
188	HD-9yjp8s98H2	15.00000000	5	main	hidden_deposit	2024-09-12 10:39:00	\N	2024-09-12 10:39:00	2024-09-12 10:39:00
191	HD-Z1JdvNKYpT	163.00000000	4501	main	hidden_deposit	2024-09-12 10:50:30	\N	2024-09-12 10:50:30	2024-09-12 10:50:30
193	WPP-xSxSKU4YA8	202.90733270	4501	main	withdraw_package_profit	2024-09-12 13:11:42	\N	2024-09-12 13:11:42	2024-09-12 13:11:42
176	ITC-NK4e9qdvdM	1190.00000000	8	main	buy_package	2024-09-08 17:34:05	\N	2024-09-08 17:34:05	2024-10-17 10:23:16
192	ITC-SNfUA4zPX9	0.00000000	8	main	buy_package	2024-09-12 12:07:24	\N	2024-09-12 12:07:24	2024-11-10 07:05:29
133	ITC-XbiBgsSJkM	1050.00000000	4485	main	buy_package	2024-08-31 10:42:04	\N	2024-08-31 10:42:04	2024-11-10 07:14:49
194	WPP-SvTlINFVJD	8.83218924	4501	main	withdraw_package_profit	2024-09-12 13:11:47	\N	2024-09-12 13:11:47	2024-09-12 13:11:47
195	WPP-KNhgWfcuQc	9.89860851	4501	main	withdraw_package_profit	2024-09-12 13:11:51	\N	2024-09-12 13:11:51	2024-09-12 13:11:51
196	WPP-WF49mq3DDT	99.11679036	4501	main	withdraw_package_profit	2024-09-12 13:11:56	\N	2024-09-12 13:11:56	2024-09-12 13:11:56
197	HD-lz0OatBAZM	100.00000000	9	main	hidden_deposit	2024-09-12 19:15:48	\N	2024-09-12 19:15:48	2024-09-12 19:15:48
198	ITC-oa4hGuM7UC	100.00000000	9	main	buy_package	2024-09-12 19:22:17	\N	2024-09-12 19:22:17	2024-09-12 19:22:17
199	HD-HQsHmsnl15	15.00000000	4464	main	hidden_deposit	2024-09-13 18:02:38	\N	2024-09-13 18:02:38	2024-09-13 18:02:38
200	WPP-IlHjAFhal3	323.05212720	4497	main	withdraw_package_profit	2024-09-15 11:08:30	\N	2024-09-15 11:08:30	2024-09-15 11:08:30
202	WPP-m5sOCjd6hh	156.35006360	4508	main	withdraw_package_profit	2024-09-15 11:30:46	\N	2024-09-15 11:30:46	2024-09-15 11:30:46
204	WPP-soP2ig3CuB	175.52541366	4503	main	withdraw_package_profit	2024-09-15 11:47:10	\N	2024-09-15 11:47:10	2024-09-15 11:47:10
206	WPP-GM9Won2OHU	175.35006360	4505	main	withdraw_package_profit	2024-09-15 11:52:00	\N	2024-09-15 11:52:00	2024-09-15 11:52:00
209	WPP-IDK3gVfQFV	22.98910355	4490	main	withdraw_package_profit	2024-09-15 15:45:58	\N	2024-09-15 15:45:58	2024-09-15 15:45:58
210	WPP-N9MPVz1x7Q	18.53289745	4490	main	withdraw_package_profit	2024-09-15 15:46:02	\N	2024-09-15 15:46:02	2024-09-15 15:46:02
211	WPP-JL9tECGhwc	11.43254684	4494	main	withdraw_package_profit	2024-09-15 15:46:59	\N	2024-09-15 15:46:59	2024-09-15 15:46:59
212	WPP-n9DiAF18l5	9.70004534	4494	main	withdraw_package_profit	2024-09-15 15:47:03	\N	2024-09-15 15:47:03	2024-09-15 15:47:03
208	WP-EXE9skcnrq	399.00000000	4501	main	withdraw	2024-09-15 18:33:42	\N	2024-09-15 15:42:45	2024-09-15 18:33:42
207	WP-EZZc5hDxWw	175.00000000	4505	main	withdraw	2024-09-15 18:55:55	\N	2024-09-15 11:54:01	2024-09-15 18:55:55
205	WP-H16R89IKSl	176.00000000	4503	main	withdraw	2024-09-15 18:55:56	\N	2024-09-15 11:49:42	2024-09-15 18:55:56
203	WP-y35YwEpsn5	156.00000000	4508	main	withdraw	2024-09-15 18:55:57	\N	2024-09-15 11:31:49	2024-09-15 18:55:57
213	HD-4S929Zpryi	15.00000000	4501	partner	hidden_deposit	2024-09-15 19:49:05	\N	2024-09-15 19:49:05	2024-09-15 19:49:05
214	HD-BIYhIKwjoo	110.00000000	10	main	hidden_deposit	2024-09-16 12:57:45	\N	2024-09-16 12:57:45	2024-09-16 12:57:45
215	HD-Xqt4PrN59g	108.00000000	6	main	hidden_deposit	2024-09-16 13:33:42	\N	2024-09-16 13:33:42	2024-09-16 13:33:42
217	HD-TAeIaCW2xA	1.00000000	4470	partner	hidden_deposit	2024-09-16 14:15:44	\N	2024-09-16 14:15:44	2024-09-16 14:15:44
218	HD-NlV6Eq1B9D	2.00000000	4470	main	hidden_deposit	2024-09-16 14:16:00	\N	2024-09-16 14:16:00	2024-09-16 14:16:00
219	HD-B7F1yLNpEi	1.00000000	4470	partner	hidden_deposit	2024-09-16 14:17:24	\N	2024-09-16 14:17:24	2024-09-16 14:17:24
221	WPP-rTn6H5sRIg	7.54358668	5	main	withdraw_package_profit	2024-09-16 16:53:53	\N	2024-09-16 16:53:53	2024-09-16 16:53:53
222	DP-VfApQmxbBB	100.00000000	5	main	deposit	2024-09-16 17:06:20	\N	2024-09-16 16:58:34	2024-09-16 17:06:20
223	WPP-VNapwJXJdr	71.27716596	4500	main	withdraw_package_profit	2024-09-17 06:09:55	\N	2024-09-17 06:09:55	2024-09-17 06:09:55
224	HD-RiTl09enuc	-84.91560772	4501	main	hidden_deposit	2024-09-17 13:04:50	\N	2024-09-17 13:04:50	2024-09-17 13:04:50
225	HD-8MUhO81v9k	-30.00000000	4501	partner	hidden_deposit	2024-09-17 13:05:02	\N	2024-09-17 13:05:02	2024-09-17 13:05:02
226	HD-pEDUM4M0jD	3.00000000	4501	main	hidden_deposit	2024-09-17 13:05:11	\N	2024-09-17 13:05:11	2024-09-17 13:05:11
227	HD-wbV8uru9Nq	-3.00000000	4501	main	hidden_deposit	2024-09-17 13:05:19	\N	2024-09-17 13:05:19	2024-09-17 13:05:19
228	HD-LzSzN0sBMo	-3.00000000	4501	main	hidden_deposit	2024-09-17 13:05:28	\N	2024-09-17 13:05:28	2024-09-17 13:05:28
229	HD-aZUFaCV9aM	3.00000000	4501	main	hidden_deposit	2024-09-17 13:05:36	\N	2024-09-17 13:05:36	2024-09-17 13:05:36
230	HD-5lITIyBu52	3.00000000	4501	partner	hidden_deposit	2024-09-17 13:05:44	\N	2024-09-17 13:05:44	2024-09-17 13:05:44
231	ITC-stchngBjHP	107.54000000	5	main	buy_package	2024-09-18 06:40:40	\N	2024-09-18 06:40:40	2024-09-18 06:40:40
232	HD-rVU8MfKpYc	100.00000000	4509	main	hidden_deposit	2024-09-18 16:50:53	\N	2024-09-18 16:50:53	2024-09-18 16:50:53
234	WPP-OEoGt4tmim	22.85827413	4485	main	withdraw_package_profit	2024-09-22 02:35:59	\N	2024-09-22 02:35:59	2024-09-22 02:35:59
235	WPP-l54i1l7m6b	11.62268617	4485	main	withdraw_package_profit	2024-09-22 02:36:02	\N	2024-09-22 02:36:02	2024-09-22 02:36:02
236	WPP-OKvvSAWr3O	26.93096247	4485	main	withdraw_package_profit	2024-09-22 02:36:14	\N	2024-09-22 02:36:14	2024-09-22 02:36:14
237	WPP-qaLdg4pFQn	2.77741800	4485	main	withdraw_package_profit	2024-09-22 02:36:16	\N	2024-09-22 02:36:16	2024-09-22 02:36:16
239	WPP-xFvUfYqSwf	94.44358980	4459	main	withdraw_package_profit	2024-09-22 04:25:47	\N	2024-09-22 04:25:47	2024-09-22 04:25:47
240	WPP-DiT8YmMcMI	3.70322400	4459	main	withdraw_package_profit	2024-09-22 04:26:41	\N	2024-09-22 04:26:41	2024-09-22 04:26:41
241	WPP-Xm07ORMS4a	21.73651503	4459	main	withdraw_package_profit	2024-09-22 04:27:15	\N	2024-09-22 04:27:15	2024-09-22 04:27:15
243	WPP-LUxXzbOzYQ	145.04911687	4501	main	withdraw_package_profit	2024-09-22 05:37:20	\N	2024-09-22 05:37:20	2024-09-22 05:37:20
244	WPP-X0BKvVWnIM	14.48578406	4501	main	withdraw_package_profit	2024-09-22 05:37:23	\N	2024-09-22 05:37:23	2024-09-22 05:37:23
245	WPP-myJVDzQu7I	12.92516883	4501	main	withdraw_package_profit	2024-09-22 05:37:27	\N	2024-09-22 05:37:27	2024-09-22 05:37:27
246	WPP-T5G2dMqmSw	296.93787812	4501	main	withdraw_package_profit	2024-09-22 05:37:33	\N	2024-09-22 05:37:33	2024-09-22 05:37:33
248	WPP-0inudkOxBs	27.21058777	4464	main	withdraw_package_profit	2024-09-22 06:31:03	\N	2024-09-22 06:31:03	2024-09-22 06:31:03
249	WPP-QwrU6QC7zI	33.15913045	4464	main	withdraw_package_profit	2024-09-22 06:31:05	\N	2024-09-22 06:31:05	2024-09-22 06:31:05
252	HD-xtBH3ZlnuE	100.00000000	4485	main	hidden_deposit	2024-09-22 14:07:02	\N	2024-09-22 14:07:02	2024-09-22 14:07:02
253	HD-2mfmNz3ejO	3.00000000	4501	partner	hidden_deposit	2024-09-22 14:21:06	\N	2024-09-22 14:21:06	2024-09-22 14:21:06
254	WPP-6aIqNaMk07	33.64262660	4490	main	withdraw_package_profit	2024-09-22 14:55:58	\N	2024-09-22 14:55:58	2024-09-22 14:55:58
255	WPP-rBi91Xglk1	22.60113035	4490	main	withdraw_package_profit	2024-09-22 14:56:03	\N	2024-09-22 14:56:03	2024-09-22 14:56:03
256	WPP-5xn0uGn4z2	13.94215243	4494	main	withdraw_package_profit	2024-09-22 14:56:30	\N	2024-09-22 14:56:30	2024-09-22 14:56:30
201	WP-agz8IdDqQR	323.00000000	4497	main	withdraw	2024-09-26 12:13:28	\N	2024-09-15 11:14:17	2024-09-26 12:13:28
250	WP-bIAfffQw7u	77.00000000	4464	main	withdraw	2024-09-26 12:20:50	\N	2024-09-22 06:38:47	2024-09-26 12:20:50
238	WP-3EcJeb9mlN	121.00000000	4485	main	withdraw	2024-09-26 12:40:15	\N	2024-09-22 02:37:22	2024-09-26 12:40:15
251	WP-hQ4azLjK5p	72.73000000	4500	main	withdraw	2024-09-26 12:52:14	\N	2024-09-22 07:40:36	2024-09-26 12:52:14
247	WP-gwbWCM6ETt	469.00000000	4501	main	withdraw	2024-09-26 13:10:47	\N	2024-09-22 06:22:25	2024-09-26 13:10:47
220	ITC-YsOvuKKHva	112.00000000	10	main	buy_package	2024-09-16 14:36:28	\N	2024-09-16 14:36:28	2024-10-01 14:09:04
216	ITC-JGR3FfLxC6	0.00000000	6	main	buy_package	2024-09-16 13:37:57	\N	2024-09-16 13:37:57	2024-10-13 15:21:56
190	ITC-mFhh9aMM9V	100.00000000	4485	main	buy_package	2024-09-12 10:49:38	\N	2024-09-12 10:49:38	2024-11-29 12:50:37
233	ITC-wIwSqATAWE	0.00000000	4509	main	buy_package	2024-09-18 17:36:09	\N	2024-09-18 17:36:09	2024-11-29 12:51:44
257	WPP-sbeAYnCeBD	11.82934236	4494	main	withdraw_package_profit	2024-09-22 14:56:32	\N	2024-09-22 14:56:32	2024-09-22 14:56:32
258	HD-MfioIVi636	100.00000000	3	main	hidden_deposit	2024-09-25 16:44:57	\N	2024-09-25 16:44:57	2024-09-25 16:44:57
260	DP-qLnfavdHS8	4280.00000000	15	main	deposit	2024-09-26 11:37:12	\N	2024-09-26 11:37:06	2024-09-26 11:37:12
261	ITC-QXGzxMvmKL	4280.00000000	15	main	buy_package	2024-09-26 11:37:47	\N	2024-09-26 11:37:47	2024-09-26 11:37:47
242	WP-cwUwK6rEcp	160.00000000	4459	main	withdraw	2024-09-26 12:17:14	\N	2024-09-22 04:39:51	2024-09-26 12:17:14
262	DP-4dAWFEqNEW	106.00000000	16	main	deposit	\N	2024-09-26 13:15:38	2024-09-26 13:15:27	2024-09-26 13:15:38
263	DP-RgdJgNcu9o	106.00000000	16	main	deposit	2024-09-26 13:15:39	\N	2024-09-26 13:15:30	2024-09-26 13:15:39
264	ITC-YyyMDt1mKa	106.00000000	16	main	buy_package	2024-09-26 13:15:57	\N	2024-09-26 13:15:57	2024-09-26 13:15:57
265	WPP-mjyDu5dFwq	15.93436247	4501	main	withdraw_package_profit	2024-09-27 05:15:45	\N	2024-09-27 05:15:45	2024-09-27 05:15:45
266	WPP-82GZiE7Ejd	159.55402856	4501	main	withdraw_package_profit	2024-09-27 05:15:49	\N	2024-09-27 05:15:49	2024-09-27 05:15:49
267	WPP-ZYTptTW2QJ	326.63166593	4501	main	withdraw_package_profit	2024-09-27 05:15:52	\N	2024-09-27 05:15:52	2024-09-27 05:15:52
268	WPP-qIBjTfk6QZ	14.21768571	4501	main	withdraw_package_profit	2024-09-27 05:15:57	\N	2024-09-27 05:15:57	2024-09-27 05:15:57
269	WPP-F3OjCsee8I	27.32261070	4500	main	withdraw_package_profit	2024-09-29 06:12:37	\N	2024-09-29 06:12:37	2024-09-29 06:12:37
271	WPP-4w6XwYpsPK	13.13746354	4459	main	withdraw_package_profit	2024-09-29 07:39:36	\N	2024-09-29 07:39:36	2024-09-29 07:39:36
272	WPP-mY1FVu5Q4i	4.07354640	4459	main	withdraw_package_profit	2024-09-29 07:39:42	\N	2024-09-29 07:39:42	2024-09-29 07:39:42
273	WP-rGaj1gDxER	18.00000000	4459	main	withdraw	2024-09-29 08:00:43	\N	2024-09-29 07:57:46	2024-09-29 08:00:43
275	HD-8uJpE1xQ0c	100.00000000	15	main	hidden_deposit	2024-09-29 16:29:05	\N	2024-09-29 16:29:05	2024-09-29 16:29:05
276	WPP-F1CeFeq0e5	1813.80959353	4471	main	withdraw_package_profit	2024-09-29 16:40:22	\N	2024-09-29 16:40:22	2024-09-29 16:40:22
279	WPP-OwBocWUbUm	37.00688925	4490	main	withdraw_package_profit	2024-09-29 18:28:30	\N	2024-09-29 18:28:30	2024-09-29 18:28:30
280	WPP-uhiKeWXaj7	24.86124338	4490	main	withdraw_package_profit	2024-09-29 18:28:38	\N	2024-09-29 18:28:38	2024-09-29 18:28:38
282	WPP-vTE2pnXo71	15.33636768	4494	main	withdraw_package_profit	2024-09-29 18:31:33	\N	2024-09-29 18:31:33	2024-09-29 18:31:33
283	WPP-f5gP2B1HpY	13.01227660	4494	main	withdraw_package_profit	2024-09-29 18:31:37	\N	2024-09-29 18:31:37	2024-09-29 18:31:37
285	WPP-9HBFyEh8oZ	43.97630634	4504	main	withdraw_package_profit	2024-09-30 16:27:37	\N	2024-09-30 16:27:37	2024-09-30 16:27:37
286	WPP-eIBaKLxroF	2.29522498	5	main	withdraw_package_profit	2024-09-30 19:17:51	\N	2024-09-30 19:17:51	2024-09-30 19:17:51
287	WPP-tqDO9eyuwr	7.69723243	5	main	withdraw_package_profit	2024-09-30 19:17:59	\N	2024-09-30 19:17:59	2024-09-30 19:17:59
288	WPP-K9brdaEAWY	2.03178025	5	main	withdraw_package_profit	2024-09-30 19:18:04	\N	2024-09-30 19:18:04	2024-09-30 19:18:04
289	WPP-MASyDWGeiF	2.10788823	10	main	withdraw_package_profit	2024-09-30 20:35:11	\N	2024-09-30 20:35:11	2024-09-30 20:35:11
270	WP-qwPvsuDRmn	27.32000000	4500	main	withdraw	2024-10-01 13:04:03	\N	2024-09-29 06:13:20	2024-10-01 13:04:03
307	WPP-4oJkhUimjH	27.88646440	4464	main	withdraw_package_profit	2024-10-06 16:10:40	\N	2024-10-06 16:10:40	2024-10-06 16:10:40
284	WP-i6Z8YCHh72	102.00000000	4494	main	withdraw	2024-10-01 13:26:38	\N	2024-09-29 18:32:25	2024-10-01 13:26:38
290	HD-xw77q7lehS	100.00000000	4470	main	hidden_deposit	2024-10-01 16:47:23	\N	2024-10-01 16:47:23	2024-10-01 16:47:23
291	HD-5UCmMBYB9F	42.00000000	4470	partner	hidden_deposit	2024-10-01 16:47:35	\N	2024-10-01 16:47:35	2024-10-01 16:47:35
293	WPP-bMQBMZxcC1	24.83873700	4500	main	withdraw_package_profit	2024-10-01 18:52:22	\N	2024-10-01 18:52:22	2024-10-01 18:52:22
294	WPP-XE5DfnAN1f	14.48578406	4501	main	withdraw_package_profit	2024-10-04 09:50:16	\N	2024-10-04 09:50:16	2024-10-04 09:50:16
295	WPP-WCgOx9hFoH	145.04911687	4501	main	withdraw_package_profit	2024-10-04 10:07:01	\N	2024-10-04 10:07:01	2024-10-04 10:07:01
296	WPP-pTjppQpVjV	296.93787812	4501	main	withdraw_package_profit	2024-10-04 10:07:05	\N	2024-10-04 10:07:05	2024-10-04 10:07:05
297	WPP-hajBZdBN70	12.92516883	4501	main	withdraw_package_profit	2024-10-04 10:07:10	\N	2024-10-04 10:07:10	2024-10-04 10:07:10
274	WP-UOtoFaDKLu	516.00000000	4501	main	withdraw	2024-10-05 16:31:45	\N	2024-09-29 08:17:06	2024-10-05 16:31:45
300	WPP-qhdsX2MFXs	56.46537059	4459	main	withdraw_package_profit	2024-10-06 09:18:49	\N	2024-10-06 09:18:49	2024-10-06 09:18:49
301	WPP-40WQPecpYA	11.94314867	4459	main	withdraw_package_profit	2024-10-06 09:18:54	\N	2024-10-06 09:18:54	2024-10-06 09:18:54
302	WPP-ewvzk0JpEo	3.70322400	4459	main	withdraw_package_profit	2024-10-06 09:18:57	\N	2024-10-06 09:18:57	2024-10-06 09:18:57
299	WP-Tpl0G5avln	12.00000000	5	main	withdraw	2024-10-06 09:52:17	\N	2024-10-06 09:12:43	2024-10-06 09:52:17
303	WPP-HTwd2PSJ7b	9.72096300	4485	main	withdraw_package_profit	2024-10-06 09:54:27	\N	2024-10-06 09:54:27	2024-10-06 09:54:27
304	HD-qX48a7oNC5	15.00000000	4513	main	hidden_deposit	2024-10-06 15:47:59	\N	2024-10-06 15:47:59	2024-10-06 15:47:59
305	HD-gVt9i3aMmv	120.00000000	4513	main	hidden_deposit	2024-10-06 16:02:23	\N	2024-10-06 16:02:23	2024-10-06 16:02:23
306	WPP-TJxTxtryAF	34.14748705	4464	main	withdraw_package_profit	2024-10-06 16:10:36	\N	2024-10-06 16:10:36	2024-10-06 16:10:36
308	WPP-LmdVU3E2DC	33.64262659	4490	main	withdraw_package_profit	2024-10-06 16:24:08	\N	2024-10-06 16:24:08	2024-10-06 16:24:08
309	WPP-32vxHXCZJF	22.60113035	4490	main	withdraw_package_profit	2024-10-06 16:24:11	\N	2024-10-06 16:24:11	2024-10-06 16:24:11
310	WPP-42103eXKmS	13.94215243	4494	main	withdraw_package_profit	2024-10-06 16:24:44	\N	2024-10-06 16:24:44	2024-10-06 16:24:44
311	WPP-dDNnYP3m51	11.82934237	4494	main	withdraw_package_profit	2024-10-06 16:24:47	\N	2024-10-06 16:24:47	2024-10-06 16:24:47
313	ITC-Dcs5Y9nS5L	135.00000000	4513	main	buy_package	2024-10-06 17:08:14	\N	2024-10-06 17:08:14	2024-10-06 17:08:14
314	HD-eZWivJs73f	100.00000000	4513	main	hidden_deposit	2024-10-06 17:14:21	\N	2024-10-06 17:14:21	2024-10-06 17:14:21
277	WP-nd1Ec0hzlM	1000.00000000	4471	main	withdraw	2024-10-06 17:19:06	\N	2024-09-29 16:54:47	2024-10-06 17:19:06
292	ITC-hKEcezNxWC	12.00000000	4470	main	buy_package	2024-10-01 17:01:02	\N	2024-10-01 17:01:02	2024-12-23 07:34:13
315	HD-FmwxnZeBjF	100.00000000	15	main	hidden_deposit	2024-10-06 17:25:06	\N	2024-10-06 17:25:06	2024-10-06 17:25:06
317	ITC-tZg0t4X5xN	100.00000000	4513	main	buy_package	2024-10-07 08:50:25	\N	2024-10-07 08:50:25	2024-10-07 08:50:25
298	WP-AJwsyRQNPx	24.80000000	4500	main	withdraw	2024-10-09 16:44:16	\N	2024-10-06 08:58:08	2024-10-09 16:44:16
281	WP-BqAtJdPbrC	212.00000000	4490	main	withdraw	2024-10-09 16:44:19	\N	2024-09-29 18:30:34	2024-10-09 16:44:19
318	WPP-zoiPDNVL6I	14.48578406	4501	main	withdraw_package_profit	2024-10-09 19:10:51	\N	2024-10-09 19:10:51	2024-10-09 19:10:51
319	WPP-WQSpeyzjmg	145.04911687	4501	main	withdraw_package_profit	2024-10-09 19:10:55	\N	2024-10-09 19:10:55	2024-10-09 19:10:55
316	WP-DA4LSAqHiX	470.00000000	4501	main	withdraw	2024-10-17 11:35:15	\N	2024-10-06 21:12:58	2024-10-17 11:35:15
278	ITC-UYLbRC56Pw	100.00000000	15	main	buy_package	2024-09-29 17:01:39	\N	2024-09-29 17:01:39	2024-12-08 19:59:15
320	WPP-pKHy5tYn1Q	296.93787812	4501	main	withdraw_package_profit	2024-10-09 19:10:58	\N	2024-10-09 19:10:58	2024-10-09 19:10:58
321	WPP-4shYUZZ9GS	12.92516883	4501	main	withdraw_package_profit	2024-10-09 19:11:05	\N	2024-10-09 19:11:05	2024-10-09 19:11:05
322	WPP-WxbY7GScxi	24.83873700	4500	main	withdraw_package_profit	2024-10-11 12:33:08	\N	2024-10-11 12:33:08	2024-10-11 12:33:08
324	WPP-jDtDB80gaF	94.34944512	4471	main	withdraw_package_profit	2024-10-13 07:08:58	\N	2024-10-13 07:08:58	2024-10-13 07:08:58
326	WPP-KVz1yUWTXa	30.12077120	4504	main	withdraw_package_profit	2024-10-13 12:49:07	\N	2024-10-13 12:49:07	2024-10-13 12:49:07
327	WPP-5aPg9dZPIT	56.46537059	4459	main	withdraw_package_profit	2024-10-13 13:55:45	\N	2024-10-13 13:55:45	2024-10-13 13:55:45
328	WPP-BRcguVtYni	11.94314867	4459	main	withdraw_package_profit	2024-10-13 13:55:47	\N	2024-10-13 13:55:47	2024-10-13 13:55:47
329	WPP-W29s3cdeWv	3.70322400	4459	main	withdraw_package_profit	2024-10-13 13:55:52	\N	2024-10-13 13:55:52	2024-10-13 13:55:52
331	DP-o3Uq1y7CGA	104.00000000	17	main	deposit	2024-10-13 14:38:33	\N	2024-10-13 14:38:29	2024-10-13 14:38:33
333	DP-rmvdt952Tt	104.00000000	17	main	deposit	\N	2024-10-13 14:38:47	2024-10-13 14:38:36	2024-10-13 14:38:47
332	DP-XgFpThrtCi	104.00000000	17	main	deposit	\N	2024-10-13 14:38:48	2024-10-13 14:38:32	2024-10-13 14:38:48
334	ITC-G5Ad3PJneB	104.00000000	17	main	buy_package	2024-10-13 14:39:01	\N	2024-10-13 14:39:01	2024-10-13 14:39:01
335	HD-tlSzHB1JCg	5.00000000	4505	partner	hidden_deposit	2024-10-13 15:13:24	\N	2024-10-13 15:13:24	2024-10-13 15:13:24
337	WPP-4YNPl7KgrY	33.64262659	4490	main	withdraw_package_profit	2024-10-13 17:43:53	\N	2024-10-13 17:43:53	2024-10-13 17:43:53
338	WPP-0RnVAxJ8bj	22.60113035	4490	main	withdraw_package_profit	2024-10-13 17:43:59	\N	2024-10-13 17:43:59	2024-10-13 17:43:59
339	WPP-nsQ4LV3uf9	13.94215243	4494	main	withdraw_package_profit	2024-10-13 17:44:39	\N	2024-10-13 17:44:39	2024-10-13 17:44:39
340	WPP-87f9BEeUdL	11.82934237	4494	main	withdraw_package_profit	2024-10-13 17:44:47	\N	2024-10-13 17:44:47	2024-10-13 17:44:47
341	WPP-XXklc9yWp1	4.62903000	4485	main	withdraw_package_profit	2024-10-14 10:28:56	\N	2024-10-14 10:28:56	2024-10-14 10:28:56
342	WPP-lvgBcYyrDd	23.95502057	4485	main	withdraw_package_profit	2024-10-14 10:29:45	\N	2024-10-14 10:29:45	2024-10-14 10:29:45
343	WPP-2415DIyMbD	12.18034594	4485	main	withdraw_package_profit	2024-10-14 10:29:48	\N	2024-10-14 10:29:48	2024-10-14 10:29:48
344	WPP-OjuJ0mkD6t	28.22311765	4485	main	withdraw_package_profit	2024-10-14 10:29:50	\N	2024-10-14 10:29:50	2024-10-14 10:29:50
345	WPP-xtKrBQoqQw	7.83975531	5	main	withdraw_package_profit	2024-10-14 12:01:53	\N	2024-10-14 12:01:53	2024-10-14 12:01:53
346	WPP-JKaYbI30fy	2.33772365	5	main	withdraw_package_profit	2024-10-14 12:01:56	\N	2024-10-14 12:01:56	2024-10-14 12:01:56
347	WPP-4c7T8iNBKK	2.06940094	5	main	withdraw_package_profit	2024-10-14 12:02:00	\N	2024-10-14 12:02:00	2024-10-14 12:02:00
348	HD-4o4etBRszm	100.00000000	4471	main	hidden_deposit	2024-10-15 16:45:37	\N	2024-10-15 16:45:37	2024-10-15 16:45:37
349	ITC-nhcMdc7OyW	100.00000000	4471	main	buy_package	2024-10-15 16:46:45	\N	2024-10-15 16:46:45	2024-10-15 16:46:45
103	ITC-ncZ6SSLhOc	1166.00000000	4512	main	buy_package	2024-08-25 13:02:04	\N	2024-08-25 13:02:04	2024-10-15 16:57:07
350	WPP-Ytxez7ZlQK	12.92516883	4501	main	withdraw_package_profit	2024-10-16 02:53:45	\N	2024-10-16 02:53:45	2024-10-16 02:53:45
351	WPP-5iUZs0G2SC	296.93787812	4501	main	withdraw_package_profit	2024-10-16 02:53:49	\N	2024-10-16 02:53:49	2024-10-16 02:53:49
352	WPP-45gJlwuycb	145.04911687	4501	main	withdraw_package_profit	2024-10-16 02:53:57	\N	2024-10-16 02:53:57	2024-10-16 02:53:57
353	WPP-8gPM6QD4E3	14.48578406	4501	main	withdraw_package_profit	2024-10-16 02:56:33	\N	2024-10-16 02:56:33	2024-10-16 02:56:33
354	HD-LMeaJdjqNl	100.00000000	14	main	hidden_deposit	2024-10-16 17:01:00	\N	2024-10-16 17:01:00	2024-10-16 17:01:00
356	DP-Prl64QnlnI	1036.00000000	8	main	deposit	2024-10-17 10:10:21	\N	2024-10-17 10:06:05	2024-10-17 10:10:21
357	ITC-y9rHXdOAhj	1036.00000000	8	main	buy_package	2024-10-17 10:11:39	\N	2024-10-17 10:11:39	2024-10-17 10:11:39
358	HD-INqrFn4htj	154.00000000	8	main	hidden_deposit	2024-10-17 10:54:38	\N	2024-10-17 10:54:38	2024-10-17 10:54:38
312	WP-vgCPb4ULrH	62.00000000	4464	main	withdraw	2024-10-17 11:01:22	\N	2024-10-06 17:01:17	2024-10-17 11:01:22
323	WP-SjW64TokfD	24.80000000	4500	main	withdraw	2024-10-17 11:03:29	\N	2024-10-13 06:31:37	2024-10-17 11:03:29
330	WP-NTsoBTbI45	144.00000000	4459	main	withdraw	2024-10-17 11:04:39	\N	2024-10-13 13:56:46	2024-10-17 11:04:39
367	DP-3qL7VipRwR	1001.00000000	18	main	deposit	2024-10-17 12:48:46	\N	2024-10-17 12:45:01	2024-10-17 12:48:46
368	DP-ibjiHc8y4c	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:19	2024-10-17 12:45:39	2024-10-17 12:49:19
369	DP-tHmviNYPtC	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:19	2024-10-17 12:45:41	2024-10-17 12:49:19
366	DP-DJ7EM3qmsy	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:20	2024-10-17 12:44:55	2024-10-17 12:49:20
365	DP-Jyx7dzDiCZ	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:21	2024-10-17 12:44:41	2024-10-17 12:49:21
364	DP-TBuS58gciA	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:22	2024-10-17 12:44:40	2024-10-17 12:49:22
363	DP-dxWtpF5Nc7	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:23	2024-10-17 12:44:38	2024-10-17 12:49:23
362	DP-1Yk4u1xu8Z	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:24	2024-10-17 12:44:37	2024-10-17 12:49:24
361	DP-KIYKwjtB8Z	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:27	2024-10-17 12:44:34	2024-10-17 12:49:27
360	DP-qxEf0YRwX7	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:39	2024-10-17 12:44:31	2024-10-17 12:49:39
359	DP-i5aOnZmvGt	1001.00000000	18	main	deposit	\N	2024-10-17 12:49:39	2024-10-17 12:44:29	2024-10-17 12:49:39
371	HD-1OkrWmkZo3	150.00000000	18	main	hidden_deposit	2024-10-17 13:06:51	\N	2024-10-17 13:06:51	2024-10-17 13:06:51
370	ITC-GvO24AQJZE	1151.00000000	18	main	buy_package	2024-10-17 13:05:13	\N	2024-10-17 13:05:13	2024-10-17 13:07:08
372	HD-q0HSeUYb6H	50.00000000	4490	partner	hidden_deposit	2024-10-17 13:17:40	\N	2024-10-17 13:17:40	2024-10-17 13:17:40
336	WP-22War0RmhU	100.00000000	4504	main	withdraw	2024-10-17 14:02:51	\N	2024-10-13 15:25:54	2024-10-17 14:02:51
373	HD-otmO5jvf4u	7.00000000	4513	partner	hidden_deposit	2024-10-17 14:04:23	\N	2024-10-17 14:04:23	2024-10-17 14:04:23
374	DP-VrDg6fyFEV	1051.00000000	19	main	deposit	2024-10-18 11:28:20	\N	2024-10-18 11:26:54	2024-10-18 11:28:20
376	HD-1ALhxV81Z1	50.00000000	4490	partner	hidden_deposit	2024-10-18 11:41:48	\N	2024-10-18 11:41:48	2024-10-18 11:41:48
377	HD-lki7vx4Oax	157.00000000	19	main	hidden_deposit	2024-10-18 12:04:38	\N	2024-10-18 12:04:38	2024-10-18 12:04:38
378	HD-eMDZdOTjh3	-469.00000000	4501	main	hidden_deposit	2024-10-18 12:10:03	\N	2024-10-18 12:10:03	2024-10-18 12:10:03
379	HD-nqjLWXEDmw	-6.00000000	4501	partner	hidden_deposit	2024-10-18 12:10:21	\N	2024-10-18 12:10:21	2024-10-18 12:10:21
380	HD-F0BGAF3tju	100.00000000	4470	main	hidden_deposit	2024-10-18 17:58:40	\N	2024-10-18 17:58:40	2024-10-18 17:58:40
381	HD-mmjSZuxUmI	-47.00000000	4470	partner	hidden_deposit	2024-10-18 18:05:12	\N	2024-10-18 18:05:12	2024-10-18 18:05:12
382	WPP-sLoMavUwDI	11.94314867	4459	main	withdraw_package_profit	2024-10-19 05:15:00	\N	2024-10-19 05:15:00	2024-10-19 05:15:00
375	ITC-IKyyNjzZE0	2208.00000000	19	main	buy_package	2024-10-18 11:30:48	\N	2024-10-18 11:30:48	2024-12-30 08:57:21
383	WPP-U7kj9a7ues	3.70322400	4459	main	withdraw_package_profit	2024-10-19 05:15:03	\N	2024-10-19 05:15:03	2024-10-19 05:15:03
386	WPP-hGYTw2aJvj	24.83873700	4500	main	withdraw_package_profit	2024-10-20 09:29:47	\N	2024-10-20 09:29:47	2024-10-20 09:29:47
388	WPP-mrFbTEEdqI	35.07277434	4464	main	withdraw_package_profit	2024-10-20 10:47:51	\N	2024-10-20 10:47:51	2024-10-20 10:47:51
389	WPP-QJmL2t6OxD	28.51615946	4464	main	withdraw_package_profit	2024-10-20 10:47:54	\N	2024-10-20 10:47:54	2024-10-20 10:47:54
390	WPP-iE3BLuJeyY	23.95502057	4485	main	withdraw_package_profit	2024-10-20 10:53:50	\N	2024-10-20 10:53:50	2024-10-20 10:53:50
391	WPP-8qfzbb26Ye	12.18034594	4485	main	withdraw_package_profit	2024-10-20 10:53:52	\N	2024-10-20 10:53:52	2024-10-20 10:53:52
392	WPP-DoqwVXmdlM	28.22311765	4485	main	withdraw_package_profit	2024-10-20 10:53:55	\N	2024-10-20 10:53:55	2024-10-20 10:53:55
393	WPP-b58pLxgfXx	4.62903000	4485	main	withdraw_package_profit	2024-10-20 10:53:56	\N	2024-10-20 10:53:56	2024-10-20 10:53:56
395	HD-kRlJEYQj9U	-120.00000000	4464	partner	hidden_deposit	2024-10-20 11:54:03	\N	2024-10-20 11:54:03	2024-10-20 11:54:03
384	WP-ZTZPqSYhRG	15.00000000	4459	main	withdraw	2024-10-20 11:55:31	\N	2024-10-20 05:33:53	2024-10-20 11:55:31
387	WP-Xbx7mu67V5	24.00000000	4500	main	withdraw	2024-10-20 11:56:57	\N	2024-10-20 09:36:13	2024-10-20 11:56:57
394	WP-sRddjidiiz	148.00000000	4485	main	withdraw	2024-10-20 12:04:30	\N	2024-10-20 10:54:39	2024-10-20 12:04:30
398	WPP-Oz73oVrTYw	15.10671964	4513	main	withdraw_package_profit	2024-10-20 13:40:23	\N	2024-10-20 13:40:23	2024-10-20 13:40:23
400	WPP-pkY6YVZRnI	138.19362900	4505	main	withdraw_package_profit	2024-10-20 13:54:03	\N	2024-10-20 13:54:03	2024-10-20 13:54:03
402	WPP-c8iAgc12pQ	230.32283400	4497	main	withdraw_package_profit	2024-10-20 14:01:27	\N	2024-10-20 14:01:27	2024-10-20 14:01:27
404	WPP-oSuXk8ugXC	94.52664421	4503	main	withdraw_package_profit	2024-10-20 14:09:49	\N	2024-10-20 14:09:49	2024-10-20 14:09:49
406	WPP-GDJaWKK94d	138.19362900	4508	main	withdraw_package_profit	2024-10-20 14:16:30	\N	2024-10-20 14:16:30	2024-10-20 14:16:30
399	WP-2zPvaBgwL7	15.00000000	4513	main	withdraw	2024-10-20 17:19:55	\N	2024-10-20 13:40:58	2024-10-20 17:19:55
397	WP-oN9MAAaOOU	12.27000000	5	main	withdraw	2024-10-20 17:21:56	\N	2024-10-20 13:23:15	2024-10-20 17:21:56
396	WP-QZ7vDwxsxk	64.00000000	4464	main	withdraw	2024-10-20 17:23:02	\N	2024-10-20 12:12:21	2024-10-20 17:23:02
408	WPP-EYdHLAbkY4	33.64262659	4490	main	withdraw_package_profit	2024-10-20 18:16:28	\N	2024-10-20 18:16:28	2024-10-20 18:16:28
409	WPP-vjwcMWVQtw	22.60113035	4490	main	withdraw_package_profit	2024-10-20 18:16:33	\N	2024-10-20 18:16:33	2024-10-20 18:16:33
410	WPP-nzOP2rlJV2	13.94215243	4494	main	withdraw_package_profit	2024-10-20 18:17:52	\N	2024-10-20 18:17:52	2024-10-20 18:17:52
411	WPP-ZSceZcGzSR	11.82934237	4494	main	withdraw_package_profit	2024-10-20 18:17:55	\N	2024-10-20 18:17:55	2024-10-20 18:17:55
412	HD-xOhXSiZWVs	13.00000000	4513	main	hidden_deposit	2024-10-21 11:52:54	\N	2024-10-21 11:52:54	2024-10-21 11:52:54
413	WPP-Odg6a1fxn2	293.03220159	4471	main	withdraw_package_profit	2024-10-21 14:53:40	\N	2024-10-21 14:53:40	2024-10-21 14:53:40
414	HD-mPaCJMKc6b	-150.00000000	4471	main	hidden_deposit	2024-10-21 14:55:42	\N	2024-10-21 14:55:42	2024-10-21 14:55:42
415	WPP-gKDIkgBlPj	48.04821538	4471	main	withdraw_package_profit	2024-10-21 14:56:16	\N	2024-10-21 14:56:16	2024-10-21 14:56:16
416	HD-lp6XAoBwiI	8.00000000	4471	main	hidden_deposit	2024-10-21 14:58:13	\N	2024-10-21 14:58:13	2024-10-21 14:58:13
417	ITC-f55SXSrqrR	220.00000000	4471	main	buy_package	2024-10-21 14:58:49	\N	2024-10-21 14:58:49	2024-10-21 15:00:30
418	HD-hcNgODBaym	20.00000000	4471	main	hidden_deposit	2024-10-21 15:13:15	\N	2024-10-21 15:13:15	2024-10-21 15:13:15
419	WPP-kz8EqsLC30	14.48578406	4501	main	withdraw_package_profit	2024-10-22 05:49:16	\N	2024-10-22 05:49:16	2024-10-22 05:49:16
420	WPP-IlVedR6xyD	145.04911687	4501	main	withdraw_package_profit	2024-10-22 05:49:20	\N	2024-10-22 05:49:20	2024-10-22 05:49:20
421	WPP-QaJijhV3gk	296.93787812	4501	main	withdraw_package_profit	2024-10-22 05:49:23	\N	2024-10-22 05:49:23	2024-10-22 05:49:23
422	WPP-0v64afSAvq	12.92516883	4501	main	withdraw_package_profit	2024-10-22 05:49:27	\N	2024-10-22 05:49:27	2024-10-22 05:49:27
423	WPP-iKm4HUW94n	4.62903000	4485	main	withdraw_package_profit	2024-10-22 09:18:46	\N	2024-10-22 09:18:46	2024-10-22 09:18:46
425	DP-Jg9C4dTpaR	408.00000000	20	main	deposit	2024-10-23 09:32:45	\N	2024-10-23 09:32:32	2024-10-23 09:32:45
424	DP-RU1sRvAveb	408.00000000	20	main	deposit	\N	2024-10-23 09:32:46	2024-10-23 09:32:13	2024-10-23 09:32:46
427	HD-MJgo3GnVIU	100.00000000	4490	main	hidden_deposit	2024-10-23 10:54:06	\N	2024-10-23 10:54:06	2024-10-23 10:54:06
428	HD-NO03X5c7As	-100.00000000	4490	partner	hidden_deposit	2024-10-23 10:54:14	\N	2024-10-23 10:54:14	2024-10-23 10:54:14
429	HD-bUNuUCNjm9	20.00000000	4505	partner	hidden_deposit	2024-10-23 11:05:48	\N	2024-10-23 11:05:48	2024-10-23 11:05:48
430	HD-s8P8GFcMeI	5.00000000	4497	partner	hidden_deposit	2024-10-23 11:07:50	\N	2024-10-23 11:07:50	2024-10-23 11:07:50
401	WP-gU0atu65rH	139.00000000	4505	main	withdraw	2024-10-23 11:16:16	\N	2024-10-20 13:58:59	2024-10-23 11:16:16
403	WP-nLOU1G7AB8	231.00000000	4497	main	withdraw	2024-10-23 11:16:17	\N	2024-10-20 14:04:20	2024-10-23 11:16:17
431	HD-R4rDdzwCZJ	100.00000000	4459	main	hidden_deposit	2024-10-24 15:33:52	\N	2024-10-24 15:33:52	2024-10-24 15:33:52
432	HD-TBVMQ630iA	100.00000000	5	main	hidden_deposit	2024-10-24 17:02:15	\N	2024-10-24 17:02:15	2024-10-24 17:02:15
433	ITC-pr2IoJIB0z	100.00000000	5	main	buy_package	2024-10-24 17:20:25	\N	2024-10-24 17:20:25	2024-10-24 17:20:25
434	WPP-NoWqUHsrmU	2.33772365	5	main	withdraw_package_profit	2024-10-24 17:25:26	\N	2024-10-24 17:25:26	2024-10-24 17:25:26
435	WPP-6Y1FCoY6az	7.83975531	5	main	withdraw_package_profit	2024-10-24 17:25:29	\N	2024-10-24 17:25:29	2024-10-24 17:25:29
436	WPP-gArqCZbuG2	2.06940094	5	main	withdraw_package_profit	2024-10-24 17:25:32	\N	2024-10-24 17:25:32	2024-10-24 17:25:32
437	DP-5v9tuiQsE2	3890.00000000	21	main	deposit	2024-10-25 09:31:42	\N	2024-10-25 09:30:47	2024-10-25 09:31:42
438	DP-8HpulY5Nvm	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:43	2024-10-25 09:31:03	2024-10-25 09:31:43
439	DP-6iTKyGTV4s	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:44	2024-10-25 09:31:05	2024-10-25 09:31:44
440	DP-DRTcDxXcF4	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:45	2024-10-25 09:31:06	2024-10-25 09:31:45
441	DP-8kOBPRHrIS	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:46	2024-10-25 09:31:08	2024-10-25 09:31:46
442	DP-dFdMcsgffH	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:47	2024-10-25 09:31:09	2024-10-25 09:31:47
443	DP-nREvCKzKux	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:48	2024-10-25 09:31:21	2024-10-25 09:31:48
444	DP-OU9KlSQMML	3890.00000000	21	main	deposit	\N	2024-10-25 09:31:49	2024-10-25 09:31:23	2024-10-25 09:31:49
407	WP-E2eWdMllud	138.00000000	4508	main	withdraw	2024-10-25 09:39:23	\N	2024-10-20 14:17:49	2024-10-25 09:39:23
405	WP-tdi6UPNBfC	94.00000000	4503	main	withdraw	2024-10-25 09:39:24	\N	2024-10-20 14:11:27	2024-10-25 09:39:24
447	HD-p9PObtm4EB	233.00000000	4470	partner	hidden_deposit	2024-10-25 09:53:47	\N	2024-10-25 09:53:47	2024-10-25 09:53:47
445	DP-4hIPpgFH8m	3890.00000000	21	main	deposit	\N	2024-10-27 09:08:28	2024-10-25 09:31:30	2024-10-27 09:08:28
426	ITC-Igk7zIiFps	5000.00000000	20	main	buy_package	2024-10-23 09:33:15	\N	2024-10-23 09:33:15	2024-12-18 11:06:06
448	HD-TwzYVhJZJZ	-233.00000000	4470	partner	hidden_deposit	2024-10-25 09:59:09	\N	2024-10-25 09:59:09	2024-10-25 09:59:09
385	WP-0lnkrT2aHZ	469.00000000	4501	main	withdraw	2024-10-25 10:50:53	\N	2024-10-20 07:26:15	2024-10-25 10:50:53
449	HD-P6YjFrTs6V	3070.00000000	21	main	hidden_deposit	2024-10-25 13:05:01	\N	2024-10-25 13:05:01	2024-10-25 13:05:01
451	WPP-DRiBDZ2GJu	57.74039649	4459	main	withdraw_package_profit	2024-10-27 07:59:14	\N	2024-10-27 07:59:14	2024-10-27 07:59:14
452	WPP-WmZhZNr4bL	11.94314867	4459	main	withdraw_package_profit	2024-10-27 07:59:16	\N	2024-10-27 07:59:16	2024-10-27 07:59:16
453	WPP-GAxuUQlmgY	3.70322400	4459	main	withdraw_package_profit	2024-10-27 07:59:19	\N	2024-10-27 07:59:19	2024-10-27 07:59:19
454	WPP-N1mhQcOPXr	24.83873700	4500	main	withdraw_package_profit	2024-10-27 14:27:41	\N	2024-10-27 14:27:41	2024-10-27 14:27:41
456	WPP-57ufVLgAjq	11.82934237	4494	main	withdraw_package_profit	2024-10-27 15:37:40	\N	2024-10-27 15:37:40	2024-10-27 15:37:40
457	WPP-9MqcUrXZfJ	13.94215243	4494	main	withdraw_package_profit	2024-10-27 15:39:18	\N	2024-10-27 15:39:18	2024-10-27 15:39:18
459	WPP-jdEoI9OvQ4	22.60113035	4490	main	withdraw_package_profit	2024-10-27 15:53:39	\N	2024-10-27 15:53:39	2024-10-27 15:53:39
460	WPP-cvwy8i6LIQ	27.27744936	19	main	withdraw_package_profit	2024-10-27 15:58:50	\N	2024-10-27 15:58:50	2024-10-27 15:58:50
461	WPP-eaHQwsUQRG	33.64262659	4490	main	withdraw_package_profit	2024-10-27 15:59:43	\N	2024-10-27 15:59:43	2024-10-27 15:59:43
458	WP-DF894pjbyx	103.00000000	4494	main	withdraw	2024-10-27 16:34:50	\N	2024-10-27 15:51:24	2024-10-27 16:34:50
462	WP-qpDWJLstbC	325.00000000	4490	main	withdraw	2024-10-27 16:34:51	\N	2024-10-27 16:02:57	2024-10-27 16:34:51
325	WP-T7hs0FmxIh	1000.00000000	4471	main	withdraw	2024-10-28 06:59:04	\N	2024-10-13 07:14:21	2024-10-28 06:59:04
463	WPP-G2tz4GMkSY	2.33772365	5	main	withdraw_package_profit	2024-10-28 15:46:09	\N	2024-10-28 15:46:09	2024-10-28 15:46:09
464	WPP-SKyu3mYMHv	7.83975531	5	main	withdraw_package_profit	2024-10-28 15:46:15	\N	2024-10-28 15:46:15	2024-10-28 15:46:15
465	WPP-CA2dZ2wEc7	2.06940094	5	main	withdraw_package_profit	2024-10-28 15:46:18	\N	2024-10-28 15:46:18	2024-10-28 15:46:18
466	WPP-rZZrlD3Eus	1.85161200	5	main	withdraw_package_profit	2024-10-28 15:46:22	\N	2024-10-28 15:46:22	2024-10-28 15:46:22
467	WPP-5PAd3N26MA	4.62903000	4485	main	withdraw_package_profit	2024-10-29 04:59:18	\N	2024-10-29 04:59:18	2024-10-29 04:59:18
468	WPP-hewrTuwTcH	14.48578406	4501	main	withdraw_package_profit	2024-11-01 00:02:57	\N	2024-11-01 00:02:57	2024-11-01 00:02:57
469	WPP-iYvPs6IrH7	145.04911687	4501	main	withdraw_package_profit	2024-11-01 00:04:16	\N	2024-11-01 00:04:16	2024-11-01 00:04:16
470	WPP-vu1ZJMFbys	296.93787812	4501	main	withdraw_package_profit	2024-11-01 00:04:20	\N	2024-11-01 00:04:20	2024-11-01 00:04:20
471	WPP-rcQVKd6jjO	12.92516883	4501	main	withdraw_package_profit	2024-11-01 00:04:23	\N	2024-11-01 00:04:23	2024-11-01 00:04:23
455	WP-qPAes8Fc22	25.76000000	4500	main	withdraw	2024-11-02 05:38:03	\N	2024-10-27 14:28:08	2024-11-02 05:38:03
450	WP-8xRauYtwVd	470.00000000	4501	main	withdraw	2024-11-03 06:23:51	\N	2024-10-27 04:04:01	2024-11-03 06:23:51
472	WPP-UCb0c0VLtc	57.74039649	4459	main	withdraw_package_profit	2024-11-03 07:57:44	\N	2024-11-03 07:57:44	2024-11-03 07:57:44
473	WPP-MSXrbK2YfV	11.94314867	4459	main	withdraw_package_profit	2024-11-03 07:57:48	\N	2024-11-03 07:57:48	2024-11-03 07:57:48
474	WPP-q7zpecOnhL	5.55483600	4459	main	withdraw_package_profit	2024-11-03 07:57:51	\N	2024-11-03 07:57:51	2024-11-03 07:57:51
475	WPP-ovEGnWBRwk	24.83873700	4500	main	withdraw_package_profit	2024-11-03 09:00:11	\N	2024-11-03 09:00:11	2024-11-03 09:00:11
477	WPP-sBV1Es059u	36.02313394	4464	main	withdraw_package_profit	2024-11-03 12:33:19	\N	2024-11-03 12:33:19	2024-11-03 12:33:19
478	WPP-nbPYMkWneR	29.16007344	4464	main	withdraw_package_profit	2024-11-03 12:33:21	\N	2024-11-03 12:33:21	2024-11-03 12:33:21
480	WPP-ZqQbKI0tRp	51.98070234	18	main	withdraw_package_profit	2024-11-03 14:17:54	\N	2024-11-03 14:17:54	2024-11-03 14:17:54
482	WPP-CWiS7ph9AB	33.64262659	4490	main	withdraw_package_profit	2024-11-03 17:14:03	\N	2024-11-03 17:14:03	2024-11-03 17:14:03
483	WPP-cIPYKNk85S	22.60113035	4490	main	withdraw_package_profit	2024-11-03 17:14:06	\N	2024-11-03 17:14:06	2024-11-03 17:14:06
484	WPP-YjDfbs37QB	13.94215243	4494	main	withdraw_package_profit	2024-11-03 17:14:33	\N	2024-11-03 17:14:33	2024-11-03 17:14:33
485	WPP-ctv2jRUv0S	11.82934237	4494	main	withdraw_package_profit	2024-11-03 17:14:35	\N	2024-11-03 17:14:35	2024-11-03 17:14:35
476	WP-1InPe48UGR	149.00000000	4459	main	withdraw	2024-11-03 17:20:20	\N	2024-11-03 10:11:18	2024-11-03 17:20:20
479	WP-7SlQDOskZE	65.00000000	4464	main	withdraw	2024-11-03 17:20:25	\N	2024-11-03 12:33:49	2024-11-03 17:20:25
481	WP-U5iD0ZfpeR	26.35000000	5	main	withdraw	2024-11-03 17:21:33	\N	2024-11-03 17:08:48	2024-11-03 17:21:33
486	WPP-URoV1LMU8c	27.27744936	19	main	withdraw_package_profit	2024-11-04 16:42:22	\N	2024-11-04 16:42:22	2024-11-04 16:42:22
487	WPP-DXUpE9VOBj	3.16377364	8	main	withdraw_package_profit	2024-11-05 08:12:39	\N	2024-11-05 08:12:39	2024-11-05 08:12:39
488	WPP-5ktJc0vQpH	31.09411714	8	main	withdraw_package_profit	2024-11-05 08:13:35	\N	2024-11-05 08:13:35	2024-11-05 08:13:35
489	WPP-Hdp3rkPdfL	19.89965541	8	main	withdraw_package_profit	2024-11-05 08:13:58	\N	2024-11-05 08:13:58	2024-11-05 08:13:58
490	HD-qFX63tolvM	100.00000000	4497	main	hidden_deposit	2024-11-05 17:37:35	\N	2024-11-05 17:37:35	2024-11-05 17:37:35
491	WPP-IurQu2QxyK	25.04907575	4485	main	withdraw_package_profit	2024-11-06 09:47:52	\N	2024-11-06 09:47:52	2024-11-06 09:47:52
492	WPP-trQxIGWAWX	12.73663728	4485	main	withdraw_package_profit	2024-11-06 09:47:53	\N	2024-11-06 09:47:53	2024-11-06 09:47:53
493	WPP-7fnYPBYKRQ	29.51210205	4485	main	withdraw_package_profit	2024-11-06 09:47:55	\N	2024-11-06 09:47:55	2024-11-06 09:47:55
494	WPP-GesbRPkC3F	4.62903000	4485	main	withdraw_package_profit	2024-11-06 09:47:57	\N	2024-11-06 09:47:57	2024-11-06 09:47:57
495	WPP-ztRKF1hpQN	12.92516883	4501	main	withdraw_package_profit	2024-11-07 23:44:39	\N	2024-11-07 23:44:39	2024-11-07 23:44:39
496	WPP-kQqctxZBaS	296.93787812	4501	main	withdraw_package_profit	2024-11-07 23:44:42	\N	2024-11-07 23:44:42	2024-11-07 23:44:42
497	WPP-TdmU84wI1c	145.04911687	4501	main	withdraw_package_profit	2024-11-07 23:44:44	\N	2024-11-07 23:44:44	2024-11-07 23:44:44
498	WPP-3bTrO5HQLt	14.48578406	4501	main	withdraw_package_profit	2024-11-07 23:44:48	\N	2024-11-07 23:44:48	2024-11-07 23:44:48
499	HD-fwooxf3SZr	100.00000000	21	main	hidden_deposit	2024-11-09 12:17:35	\N	2024-11-09 12:17:35	2024-11-09 12:17:35
500	ITC-0zjnsbw0wu	100.00000000	21	main	buy_package	2024-11-09 12:20:19	\N	2024-11-09 12:20:19	2024-11-09 12:20:19
502	WPP-h9DAvjRGmd	24.83873700	4500	main	withdraw_package_profit	2024-11-10 06:35:28	\N	2024-11-10 06:35:28	2024-11-10 06:35:28
504	HD-4zf5RSczqM	-200.00000000	4459	main	hidden_deposit	2024-11-10 07:03:53	\N	2024-11-10 07:03:53	2024-11-10 07:03:53
505	HD-S6IUSJpabU	-150.00000000	8	main	hidden_deposit	2024-11-10 07:05:53	\N	2024-11-10 07:05:53	2024-11-10 07:05:53
506	HD-HWR8Dd9Q6X	-150.00000000	4485	main	hidden_deposit	2024-11-10 07:06:35	\N	2024-11-10 07:06:35	2024-11-10 07:06:35
507	HD-D8veOCNjKl	-100.00000000	4485	main	hidden_deposit	2024-11-10 07:15:05	\N	2024-11-10 07:15:05	2024-11-10 07:15:05
501	WP-KCIhA24JMt	81.00000000	4485	main	withdraw	2024-11-12 18:39:27	\N	2024-11-10 02:47:37	2024-11-12 18:39:27
446	ITC-vAwQY8gH2H	7840.00000000	21	main	buy_package	2024-10-25 09:34:18	\N	2024-10-25 09:34:18	2024-12-09 17:06:18
508	HD-1EiY9wgc3d	-210.00000000	4459	main	hidden_deposit	2024-11-10 07:34:53	\N	2024-11-10 07:34:53	2024-11-10 07:34:53
509	WPP-sOJ34IDYI1	2.33772365	5	main	withdraw_package_profit	2024-11-10 09:08:05	\N	2024-11-10 09:08:05	2024-11-10 09:08:05
510	WPP-PLUVuNk8Yv	7.83975531	5	main	withdraw_package_profit	2024-11-10 09:08:08	\N	2024-11-10 09:08:08	2024-11-10 09:08:08
511	WPP-agK3Hq7l5g	2.06940094	5	main	withdraw_package_profit	2024-11-10 09:08:11	\N	2024-11-10 09:08:11	2024-11-10 09:08:11
512	WPP-YosCQ9EwKi	1.85161200	5	main	withdraw_package_profit	2024-11-10 09:08:15	\N	2024-11-10 09:08:15	2024-11-10 09:08:15
513	WPP-IGHkZNARgS	13.94215243	4494	main	withdraw_package_profit	2024-11-10 13:03:32	\N	2024-11-10 13:03:32	2024-11-10 13:03:32
514	WPP-NpQE1LxoPS	11.82934237	4494	main	withdraw_package_profit	2024-11-10 13:03:35	\N	2024-11-10 13:03:35	2024-11-10 13:03:35
515	WPP-kyQhXpWoOw	33.64262659	4490	main	withdraw_package_profit	2024-11-10 13:04:43	\N	2024-11-10 13:04:43	2024-11-10 13:04:43
516	WPP-kb67d2igqS	22.60113035	4490	main	withdraw_package_profit	2024-11-10 13:04:46	\N	2024-11-10 13:04:46	2024-11-10 13:04:46
517	WPP-zlfCbuUQMt	11.94314867	4459	main	withdraw_package_profit	2024-11-10 16:00:04	\N	2024-11-10 16:00:04	2024-11-10 16:00:04
518	WPP-HmMyHMPr46	57.74039649	4459	main	withdraw_package_profit	2024-11-10 16:00:08	\N	2024-11-10 16:00:08	2024-11-10 16:00:08
519	WPP-OwKwCl6IPt	5.55483600	4459	main	withdraw_package_profit	2024-11-10 16:00:11	\N	2024-11-10 16:00:11	2024-11-10 16:00:11
520	HD-0c0CkkufZw	100.00000000	4485	main	hidden_deposit	2024-11-11 05:47:50	\N	2024-11-11 05:47:50	2024-11-11 05:47:50
521	WPP-hM1qxFelPb	0.38635564	8	main	withdraw_package_profit	2024-11-11 10:22:31	\N	2024-11-11 10:22:31	2024-11-11 10:22:31
522	WPP-IjaBnhq2Mq	31.09411714	8	main	withdraw_package_profit	2024-11-11 10:22:41	\N	2024-11-11 10:22:41	2024-11-11 10:22:41
523	WPP-GOsJ9CiaqZ	19.89965541	8	main	withdraw_package_profit	2024-11-11 10:22:51	\N	2024-11-11 10:22:51	2024-11-11 10:22:51
524	WPP-1qe6TyXDFA	54.55489872	19	main	withdraw_package_profit	2024-11-12 10:39:48	\N	2024-11-12 10:39:48	2024-11-12 10:39:48
525	HD-PoUUcqPD1j	1127.00000000	34	main	hidden_deposit	2024-11-12 13:01:40	\N	2024-11-12 13:01:40	2024-11-12 13:01:40
526	HD-3e995VU6gg	1127.00000000	35	main	hidden_deposit	2024-11-12 13:02:03	\N	2024-11-12 13:02:03	2024-11-12 13:02:03
528	ITC-AySSuDO0gi	1127.00000000	35	main	buy_package	2024-11-12 13:55:30	\N	2024-11-12 13:55:30	2024-11-12 13:55:30
503	WP-2axJPSuZZG	49.60000000	4500	main	withdraw	2024-11-12 18:37:29	\N	2024-11-10 06:37:06	2024-11-12 18:37:29
529	WPP-vuw12qmWiN	3.70322400	4485	main	withdraw_package_profit	2024-11-13 03:35:47	\N	2024-11-13 03:35:47	2024-11-13 03:35:47
530	WPP-9ZjRrVSWye	51.98070234	18	main	withdraw_package_profit	2024-11-13 07:14:46	\N	2024-11-13 07:14:46	2024-11-13 07:14:46
531	WPP-xEV2DSIQRg	14.48578406	4501	main	withdraw_package_profit	2024-11-14 09:25:00	\N	2024-11-14 09:25:00	2024-11-14 09:25:00
532	WPP-jCZWtlWweX	145.04911687	4501	main	withdraw_package_profit	2024-11-14 09:25:04	\N	2024-11-14 09:25:04	2024-11-14 09:25:04
533	WPP-ausVlzAS0F	296.93787812	4501	main	withdraw_package_profit	2024-11-14 09:25:06	\N	2024-11-14 09:25:06	2024-11-14 09:25:06
534	WPP-PuHnL1jaYc	12.92516883	4501	main	withdraw_package_profit	2024-11-14 09:25:16	\N	2024-11-14 09:25:16	2024-11-14 09:25:16
535	WPP-mWDtWgspc0	11.94314867	4459	main	withdraw_package_profit	2024-11-15 10:31:58	\N	2024-11-15 10:31:58	2024-11-15 10:31:58
536	WPP-1UzmmMKmLL	1.85161200	4459	main	withdraw_package_profit	2024-11-15 10:32:01	\N	2024-11-15 10:32:01	2024-11-15 10:32:01
537	WPP-jmD5J96aqn	52.99845579	4459	main	withdraw_package_profit	2024-11-15 10:32:04	\N	2024-11-15 10:32:04	2024-11-15 10:32:04
538	HD-RtaxU2bs5j	1000.00000000	4459	main	hidden_deposit	2024-11-15 15:57:05	\N	2024-11-15 15:57:05	2024-11-15 15:57:05
540	HD-UtuCTZfwDj	150.00000000	4459	main	hidden_deposit	2024-11-16 13:14:13	\N	2024-11-16 13:14:13	2024-11-16 13:14:13
539	ITC-lykjDQVfnF	1150.00000000	4459	main	buy_package	2024-11-15 16:00:59	\N	2024-11-15 16:00:59	2024-11-16 13:14:34
541	WPP-uUh7Bknihq	2.33772365	5	main	withdraw_package_profit	2024-11-17 07:23:26	\N	2024-11-17 07:23:26	2024-11-17 07:23:26
542	WPP-detCw6qbQJ	7.83975531	5	main	withdraw_package_profit	2024-11-17 07:23:28	\N	2024-11-17 07:23:28	2024-11-17 07:23:28
543	WPP-oa5dSJkuCf	2.06940094	5	main	withdraw_package_profit	2024-11-17 07:23:31	\N	2024-11-17 07:23:31	2024-11-17 07:23:31
544	WPP-eyaDezAbfS	1.85161200	5	main	withdraw_package_profit	2024-11-17 07:23:34	\N	2024-11-17 07:23:34	2024-11-17 07:23:34
545	WP-fWtRNv7sns	28.00000000	5	main	withdraw	2024-11-17 08:28:18	\N	2024-11-17 07:25:54	2024-11-17 08:28:18
546	WP-cJmwA7Qxay	142.00000000	4459	main	withdraw	2024-11-17 10:30:13	\N	2024-11-17 07:43:24	2024-11-17 10:30:13
548	WPP-dFj0YDsV8b	33.64262659	4490	main	withdraw_package_profit	2024-11-17 11:28:25	\N	2024-11-17 11:28:25	2024-11-17 11:28:25
549	WPP-ZOyWxbxEHb	22.60113035	4490	main	withdraw_package_profit	2024-11-17 11:28:29	\N	2024-11-17 11:28:29	2024-11-17 11:28:29
550	WPP-lYxfA2ibUl	13.94215243	4494	main	withdraw_package_profit	2024-11-17 11:35:51	\N	2024-11-17 11:35:51	2024-11-17 11:35:51
551	WPP-gMkQEtykrk	11.82934237	4494	main	withdraw_package_profit	2024-11-17 11:35:56	\N	2024-11-17 11:35:56	2024-11-17 11:35:56
552	WP-bgbWN0FS9o	109.00000000	19	main	withdraw	2024-11-17 12:02:40	\N	2024-11-17 11:41:14	2024-11-17 12:02:40
553	DP-xYRkhZOJbb	1980.00000000	36	main	deposit	2024-11-17 12:18:02	\N	2024-11-17 12:09:16	2024-11-17 12:18:02
555	WPP-NlypDCzMEO	36.99924523	4464	main	withdraw_package_profit	2024-11-17 13:21:14	\N	2024-11-17 13:21:14	2024-11-17 13:21:14
556	WPP-sl5MH7FSl3	29.81852744	4464	main	withdraw_package_profit	2024-11-17 13:21:16	\N	2024-11-17 13:21:16	2024-11-17 13:21:16
558	HD-uWWcpqQqo1	100.00000000	30	main	hidden_deposit	2024-11-17 13:55:56	\N	2024-11-17 13:55:56	2024-11-17 13:55:56
557	WP-X7Jlku9Fnd	67.00000000	4464	main	withdraw	2024-11-17 17:36:03	\N	2024-11-17 13:22:15	2024-11-17 17:36:03
554	ITC-J7EPLe7ruL	2277.00000000	36	main	buy_package	2024-11-17 12:25:43	\N	2024-11-17 12:25:43	2024-11-17 17:49:16
560	HD-IIaGHiRDXn	297.00000000	36	main	hidden_deposit	2024-11-17 17:49:32	\N	2024-11-17 17:49:32	2024-11-17 17:49:32
561	HD-YoR3dRdBEk	100.00000000	4490	main	hidden_deposit	2024-11-17 17:50:15	\N	2024-11-17 17:50:15	2024-11-17 17:50:15
562	HD-bqpnAQucAw	118.00000000	4490	partner	hidden_deposit	2024-11-17 17:51:32	\N	2024-11-17 17:51:32	2024-11-17 17:51:32
563	ITC-3Rh4ox62hn	100.00000000	4490	main	buy_package	2024-11-17 17:52:10	\N	2024-11-17 17:52:10	2024-11-17 17:52:10
564	HD-FT9zFqM6Wi	49.00000000	4464	partner	hidden_deposit	2024-11-17 17:53:46	\N	2024-11-17 17:53:46	2024-11-17 17:53:46
565	HD-jH2vGdGvI8	-49.00000000	4464	partner	hidden_deposit	2024-11-17 18:33:28	\N	2024-11-17 18:33:28	2024-11-17 18:33:28
566	WPP-oHUfag91oQ	74.13854448	4503	main	withdraw_package_profit	2024-11-17 19:49:47	\N	2024-11-17 19:49:47	2024-11-17 19:49:47
568	WPP-2iyqPVxjOY	180.64536000	4497	main	withdraw_package_profit	2024-11-17 19:55:42	\N	2024-11-17 19:55:42	2024-11-17 19:55:42
570	WPP-e9GoSkpZ0k	108.38716000	4505	main	withdraw_package_profit	2024-11-17 20:01:48	\N	2024-11-17 20:01:48	2024-11-17 20:01:48
569	WP-eUIovfLflA	180.00000000	4497	main	withdraw	2024-11-18 06:52:48	\N	2024-11-17 19:57:38	2024-11-18 06:52:48
559	ITC-dI9XrtHs6d	200.00000000	30	main	buy_package	2024-11-17 15:24:43	\N	2024-11-17 15:24:43	2024-11-24 13:35:48
527	ITC-MO95F6ARHa	1627.00000000	34	main	buy_package	2024-11-12 13:48:39	\N	2024-11-12 13:48:39	2025-01-06 09:29:42
572	WPP-zYuruTPppi	108.38716000	4508	main	withdraw_package_profit	2024-11-17 20:08:13	\N	2024-11-17 20:08:13	2024-11-17 20:08:13
547	WP-Hdl3owozj5	1408.52000000	4501	main	withdraw	2024-11-18 06:15:52	\N	2024-11-17 08:27:25	2024-11-18 06:15:52
574	WP-EApxdk3Pg6	103.00000000	18	main	withdraw	2024-11-18 06:17:35	\N	2024-11-17 20:27:26	2024-11-18 06:17:35
567	WP-KYaHMp5KvT	74.00000000	4503	main	withdraw	2024-11-18 06:52:46	\N	2024-11-17 19:52:34	2024-11-18 06:52:46
575	WPP-FjemJLfi7v	0.38635564	8	main	withdraw_package_profit	2024-11-18 10:07:14	\N	2024-11-18 10:07:14	2024-11-18 10:07:14
576	WPP-bqGCOIJJn4	31.09411714	8	main	withdraw_package_profit	2024-11-18 10:07:23	\N	2024-11-18 10:07:23	2024-11-18 10:07:23
577	WPP-7k1NxyuUOY	19.89965541	8	main	withdraw_package_profit	2024-11-18 10:07:29	\N	2024-11-18 10:07:29	2024-11-18 10:07:29
578	WPP-XeWGKhlOgI	4.30773719	4470	main	withdraw_package_profit	2024-11-18 19:08:38	\N	2024-11-18 19:08:38	2024-11-18 19:08:38
579	WPP-Kq8Wt7lDo5	39.37385727	4470	main	withdraw_package_profit	2024-11-18 19:08:40	\N	2024-11-18 19:08:40	2024-11-18 19:08:40
580	HD-dsA9gbRy8Z	100.00000000	3	main	hidden_deposit	2024-11-20 13:36:55	\N	2024-11-20 13:36:55	2024-11-20 13:36:55
581	HD-Mjb6FsupYL	490.00000000	37	main	hidden_deposit	2024-11-20 17:01:24	\N	2024-11-20 17:01:24	2024-11-20 17:01:24
582	ITC-lvrnrUsdGJ	490.00000000	37	main	buy_package	2024-11-20 17:02:32	\N	2024-11-20 17:02:32	2024-11-20 17:02:32
583	HD-riyYYUkPjZ	14.00000000	20	partner	hidden_deposit	2024-11-20 17:03:39	\N	2024-11-20 17:03:39	2024-11-20 17:03:39
573	WP-4jz2LnfOSH	108.00000000	4508	main	withdraw	2024-11-20 17:05:47	\N	2024-11-17 20:09:42	2024-11-20 17:05:47
571	WP-iIL8xyF8Mg	108.00000000	4505	main	withdraw	2024-11-20 17:05:48	\N	2024-11-17 20:02:59	2024-11-20 17:05:48
584	DP-X9ntpshfKE	980.00000000	38	main	deposit	2024-11-21 12:18:38	\N	2024-11-21 12:17:59	2024-11-21 12:18:38
586	HD-iPj159yYGI	49.00000000	4505	partner	hidden_deposit	2024-11-21 12:35:12	\N	2024-11-21 12:35:12	2024-11-21 12:35:12
587	HD-dwSc4WbC1x	9.00000000	4497	partner	hidden_deposit	2024-11-21 12:35:43	\N	2024-11-21 12:35:43	2024-11-21 12:35:43
588	WPP-wuXOEpdwGI	25.61470066	4485	main	withdraw_package_profit	2024-11-22 08:37:08	\N	2024-11-22 08:37:08	2024-11-22 08:37:08
589	WPP-26jS5QPYib	13.02423908	4485	main	withdraw_package_profit	2024-11-22 08:37:10	\N	2024-11-22 08:37:10	2024-11-22 08:37:10
590	WPP-QVLi985Q5q	3.70322400	4485	main	withdraw_package_profit	2024-11-22 08:37:12	\N	2024-11-22 08:37:12	2024-11-22 08:37:12
591	WPP-7R9r81DsOf	27.86944943	4485	main	withdraw_package_profit	2024-11-22 08:37:14	\N	2024-11-22 08:37:14	2024-11-22 08:37:14
592	WPP-LqnCmwbBY8	1.85161200	5	main	withdraw_package_profit	2024-11-22 19:34:57	\N	2024-11-22 19:34:57	2024-11-22 19:34:57
593	WPP-5dijMpBwdT	2.06940094	5	main	withdraw_package_profit	2024-11-22 19:35:00	\N	2024-11-22 19:35:00	2024-11-22 19:35:00
594	WPP-yFzEDbnVid	7.83975531	5	main	withdraw_package_profit	2024-11-22 19:35:03	\N	2024-11-22 19:35:03	2024-11-22 19:35:03
595	WPP-0L673CbpKo	2.33772365	5	main	withdraw_package_profit	2024-11-22 19:35:05	\N	2024-11-22 19:35:05	2024-11-22 19:35:05
596	DP-tTqg0WMFc9	962.00000000	39	main	deposit	2024-11-23 12:35:09	\N	2024-11-23 12:34:54	2024-11-23 12:35:09
597	ITC-N6BOPMHJ5z	962.00000000	39	main	buy_package	2024-11-23 12:35:30	\N	2024-11-23 12:35:30	2024-11-23 12:35:30
598	HD-udF5oxjNTx	53.00000000	20	partner	hidden_deposit	2024-11-23 12:37:07	\N	2024-11-23 12:37:07	2024-11-23 12:37:07
599	HD-UkqWm0J8hY	9.00000000	4505	partner	hidden_deposit	2024-11-23 12:38:51	\N	2024-11-23 12:38:51	2024-11-23 12:38:51
600	WPP-Trn19mHFQU	14.48578406	4501	main	withdraw_package_profit	2024-11-24 02:56:24	\N	2024-11-24 02:56:24	2024-11-24 02:56:24
601	WPP-jvoqcYjmvS	145.04911687	4501	main	withdraw_package_profit	2024-11-24 02:56:28	\N	2024-11-24 02:56:28	2024-11-24 02:56:28
602	WPP-Lz8VJtL3nL	296.93787812	4501	main	withdraw_package_profit	2024-11-24 02:56:31	\N	2024-11-24 02:56:31	2024-11-24 02:56:31
603	WPP-VQZpLUsVin	12.92516883	4501	main	withdraw_package_profit	2024-11-24 02:56:34	\N	2024-11-24 02:56:34	2024-11-24 02:56:34
605	WPP-e9wou7RTbA	112.99614973	15	main	withdraw_package_profit	2024-11-24 03:04:15	\N	2024-11-24 03:04:15	2024-11-24 03:04:15
606	WP-vHAaaOEpIr	105.00000000	15	main	withdraw	2024-11-24 07:08:40	\N	2024-11-24 05:06:41	2024-11-24 07:08:40
607	WP-91TeiP8vjY	14.25000000	5	main	withdraw	2024-11-24 07:10:11	\N	2024-11-24 05:58:06	2024-11-24 07:10:11
604	WP-vDJ2Wx4xvI	469.00000000	4501	main	withdraw	2024-11-24 07:10:17	\N	2024-11-24 02:57:48	2024-11-24 07:10:17
608	WP-iB73duekQa	74.00000000	4485	main	withdraw	2024-11-24 08:51:33	\N	2024-11-24 07:58:57	2024-11-24 08:51:33
609	HD-CHxHOstxOO	-118.00000000	4490	partner	hidden_deposit	2024-11-24 12:26:10	\N	2024-11-24 12:26:10	2024-11-24 12:26:10
610	HD-FVYcj7yLVv	118.00000000	4490	main	hidden_deposit	2024-11-24 12:26:18	\N	2024-11-24 12:26:18	2024-11-24 12:26:18
611	WPP-F7Gaa6UEZx	33.64262659	4490	main	withdraw_package_profit	2024-11-24 13:06:41	\N	2024-11-24 13:06:41	2024-11-24 13:06:41
612	WPP-Rq3m7vnZGK	22.60113035	4490	main	withdraw_package_profit	2024-11-24 13:06:45	\N	2024-11-24 13:06:45	2024-11-24 13:06:45
614	WPP-AbP4gELvY4	13.94215243	4494	main	withdraw_package_profit	2024-11-24 13:09:18	\N	2024-11-24 13:09:18	2024-11-24 13:09:18
615	WPP-p4hjPxWumJ	11.82934237	4494	main	withdraw_package_profit	2024-11-24 13:09:22	\N	2024-11-24 13:09:22	2024-11-24 13:09:22
617	HD-0dz28uHMxp	100.00000000	30	main	hidden_deposit	2024-11-24 13:35:40	\N	2024-11-24 13:35:40	2024-11-24 13:35:40
618	HD-UbItRmZtCx	67.00000000	20	main	hidden_deposit	2024-11-24 15:35:48	\N	2024-11-24 15:35:48	2024-11-24 15:35:48
619	HD-sh9wzFE2jp	-67.00000000	20	partner	hidden_deposit	2024-11-24 15:35:59	\N	2024-11-24 15:35:59	2024-11-24 15:35:59
620	WP-ZIRiSH6Cnv	67.00000000	20	main	withdraw	2024-11-24 19:33:41	\N	2024-11-24 19:28:40	2024-11-24 19:33:41
616	WP-DYrYVABTXC	103.00000000	4494	main	withdraw	2024-11-24 19:35:36	\N	2024-11-24 13:10:10	2024-11-24 19:35:36
621	HD-d63WYMhPcd	970.00000000	38	main	hidden_deposit	2024-11-25 09:21:04	\N	2024-11-25 09:21:04	2024-11-25 09:21:04
613	WP-s2WFd5qzhQ	343.00000000	4490	main	withdraw	2024-11-25 09:34:38	\N	2024-11-24 13:08:24	2024-11-25 09:34:38
622	HD-UTf8BDhJtZ	-83.00000000	4505	partner	hidden_deposit	2024-11-25 10:09:34	\N	2024-11-25 10:09:34	2024-11-25 10:09:34
623	HD-f13djPh0JH	-14.00000000	4497	partner	hidden_deposit	2024-11-25 10:10:03	\N	2024-11-25 10:10:03	2024-11-25 10:10:03
624	WPP-Y6VidELOXC	39.37385727	4470	main	withdraw_package_profit	2024-11-25 10:37:50	\N	2024-11-25 10:37:50	2024-11-25 10:37:50
625	WPP-wrteIK6iPL	4.30773719	4470	main	withdraw_package_profit	2024-11-25 10:37:54	\N	2024-11-25 10:37:54	2024-11-25 10:37:54
626	WPP-MAFlZ7KWkS	0.38635564	8	main	withdraw_package_profit	2024-11-25 14:39:08	\N	2024-11-25 14:39:08	2024-11-25 14:39:08
627	WPP-IA4SALsJcx	31.09411714	8	main	withdraw_package_profit	2024-11-25 14:39:14	\N	2024-11-25 14:39:14	2024-11-25 14:39:14
628	WPP-Tz6LNwKZh7	19.89965541	8	main	withdraw_package_profit	2024-11-25 14:39:22	\N	2024-11-25 14:39:22	2024-11-25 14:39:22
629	WPP-rLqsVvF81F	3.70322400	4485	main	withdraw_package_profit	2024-11-26 08:44:59	\N	2024-11-26 08:44:59	2024-11-26 08:44:59
630	HD-qmUpxQLkBl	100.00000000	34	main	hidden_deposit	2024-11-28 18:17:34	\N	2024-11-28 18:17:34	2024-11-28 18:17:34
631	HD-f4jfcsOCGi	30.00000000	34	partner	hidden_deposit	2024-11-28 18:17:50	\N	2024-11-28 18:17:50	2024-11-28 18:17:50
585	ITC-FjvE5ODTlu	5915.00000000	38	main	buy_package	2024-11-21 12:22:01	\N	2024-11-21 12:22:01	2024-12-28 10:58:24
633	HD-PDQAVfJ7jA	100.00000000	42	main	hidden_deposit	2024-11-29 11:53:20	\N	2024-11-29 11:53:20	2024-11-29 11:53:20
634	ITC-m3MRU2bJ9F	100.00000000	42	main	buy_package	2024-11-29 11:55:17	\N	2024-11-29 11:55:17	2024-11-29 11:55:17
635	HD-vQtF0vgC6R	-100.00000000	4485	main	hidden_deposit	2024-11-29 12:50:58	\N	2024-11-29 12:50:58	2024-11-29 12:50:58
636	HD-jqQHhfVHL1	-100.00000000	4509	main	hidden_deposit	2024-11-29 12:51:58	\N	2024-11-29 12:51:58	2024-11-29 12:51:58
259	ITC-ze20g04VB2	100.00000000	3	main	buy_package	2024-09-26 06:48:56	\N	2024-09-26 06:48:56	2024-11-29 14:46:20
637	HD-hVhAs4quVX	-100.00000000	3	main	hidden_deposit	2024-11-29 14:46:34	\N	2024-11-29 14:46:34	2024-11-29 14:46:34
638	HD-aXkrGv3Ovz	955.00000000	38	main	hidden_deposit	2024-11-29 14:48:06	\N	2024-11-29 14:48:06	2024-11-29 14:48:06
639	WPP-NlCPF9Zxqp	296.93787812	4501	main	withdraw_package_profit	2024-11-30 01:37:48	\N	2024-11-30 01:37:48	2024-11-30 01:37:48
640	WPP-U7d5vojBYc	12.92516883	4501	main	withdraw_package_profit	2024-11-30 01:37:52	\N	2024-11-30 01:37:52	2024-11-30 01:37:52
641	WPP-nchl4DzMJi	14.48578406	4501	main	withdraw_package_profit	2024-11-30 01:37:55	\N	2024-11-30 01:37:55	2024-11-30 01:37:55
642	WPP-l4fjZqNFJD	145.04911687	4501	main	withdraw_package_profit	2024-11-30 01:37:58	\N	2024-11-30 01:37:58	2024-11-30 01:37:58
643	HD-DACRBR9HMw	100.00000000	43	main	hidden_deposit	2024-11-30 15:12:49	\N	2024-11-30 15:12:49	2024-11-30 15:12:49
644	ITC-Z692r80YQL	100.00000000	43	main	buy_package	2024-11-30 15:14:38	\N	2024-11-30 15:14:38	2024-11-30 15:14:38
645	WPP-aF9u6nvZME	323.24920573	4471	main	withdraw_package_profit	2024-11-30 20:34:47	\N	2024-11-30 20:34:47	2024-11-30 20:34:47
647	HD-n6rjvpNDzN	100.00000000	44	main	hidden_deposit	2024-12-01 06:17:48	\N	2024-12-01 06:17:48	2024-12-01 06:17:48
648	ITC-GgvO41uKHy	100.00000000	44	main	buy_package	2024-12-01 06:23:36	\N	2024-12-01 06:23:36	2024-12-01 06:23:36
649	WPP-gQfUcP2IjL	38.00180601	4464	main	withdraw_package_profit	2024-12-01 10:32:10	\N	2024-12-01 10:32:10	2024-12-01 10:32:10
650	WPP-Caqv5blQ7p	30.49184977	4464	main	withdraw_package_profit	2024-12-01 10:32:14	\N	2024-12-01 10:32:14	2024-12-01 10:32:14
652	HD-scy1QIzbAp	100.00000000	22	main	hidden_deposit	2024-12-01 13:49:47	\N	2024-12-01 13:49:47	2024-12-01 13:49:47
653	ITC-xNQZsYmvwv	100.00000000	22	main	buy_package	2024-12-01 14:03:35	\N	2024-12-01 14:03:35	2024-12-01 14:03:35
651	WP-MPCoIQlZbr	68.00000000	4464	main	withdraw	2024-12-01 15:40:44	\N	2024-12-01 10:32:33	2024-12-01 15:40:44
646	WP-sbllhxc2qI	469.00000000	4501	main	withdraw	2024-12-01 16:10:18	\N	2024-12-01 03:48:34	2024-12-01 16:10:18
654	WP-6YDFmMloRP	323.00000000	4471	main	withdraw	2024-12-01 16:15:40	\N	2024-12-01 16:11:47	2024-12-01 16:15:40
655	WPP-bkbdUeZakj	3.99595000	3	main	withdraw_package_profit	2024-12-02 06:50:51	\N	2024-12-02 06:50:51	2024-12-02 06:50:51
656	WPP-5KlVH4sWNx	4.71911338	3	main	withdraw_package_profit	2024-12-02 06:51:02	\N	2024-12-02 06:51:02	2024-12-02 06:51:02
657	WPP-QDrClF8Qmq	0.39794631	8	main	withdraw_package_profit	2024-12-02 13:56:43	\N	2024-12-02 13:56:43	2024-12-02 13:56:43
658	WPP-9AbCZCWArE	32.02694065	8	main	withdraw_package_profit	2024-12-02 13:56:58	\N	2024-12-02 13:56:58	2024-12-02 13:56:58
659	WPP-i5LVVCQjZE	20.49664507	8	main	withdraw_package_profit	2024-12-02 13:57:07	\N	2024-12-02 13:57:07	2024-12-02 13:57:07
660	HD-L5AuiJjEgr	-87.36318892	4470	main	hidden_deposit	2024-12-03 16:11:29	\N	2024-12-03 16:11:29	2024-12-03 16:11:29
661	WPP-IFaFtGooz4	44.09688722	39	main	withdraw_package_profit	2024-12-03 16:22:29	\N	2024-12-03 16:22:29	2024-12-03 16:22:29
662	WPP-KYA4qTZyCK	4.74557901	5	main	withdraw_package_profit	2024-12-03 21:54:19	\N	2024-12-03 21:54:19	2024-12-03 21:54:19
663	WPP-5aG0cg6GoF	15.91470328	5	main	withdraw_package_profit	2024-12-03 21:54:23	\N	2024-12-03 21:54:23	2024-12-03 21:54:23
664	WPP-oDBSzhcLLI	4.20088391	5	main	withdraw_package_profit	2024-12-03 21:54:26	\N	2024-12-03 21:54:26	2024-12-03 21:54:26
665	WPP-0GlP65O7R7	3.75877236	5	main	withdraw_package_profit	2024-12-03 21:54:29	\N	2024-12-03 21:54:29	2024-12-03 21:54:29
666	WPP-96LgFwsLdt	26.97889069	4485	main	withdraw_package_profit	2024-12-04 15:27:21	\N	2024-12-04 15:27:21	2024-12-04 15:27:21
667	WPP-vz3boHSLWM	13.71788518	4485	main	withdraw_package_profit	2024-12-04 15:27:24	\N	2024-12-04 15:27:24	2024-12-04 15:27:24
668	WPP-DLhjQ2Lv7l	1.90716036	4485	main	withdraw_package_profit	2024-12-04 15:27:26	\N	2024-12-04 15:27:26	2024-12-04 15:27:26
669	WPP-zlTK6eR38j	29.35372307	4485	main	withdraw_package_profit	2024-12-04 15:27:29	\N	2024-12-04 15:27:29	2024-12-04 15:27:29
670	WPP-4oRXTE8RHb	305.84601446	4501	main	withdraw_package_profit	2024-12-04 16:28:58	\N	2024-12-04 16:28:58	2024-12-04 16:28:58
671	WPP-Vputyy4PlW	13.31292389	4501	main	withdraw_package_profit	2024-12-04 16:29:00	\N	2024-12-04 16:29:00	2024-12-04 16:29:00
672	WPP-BaKsbebaUC	14.92035758	4501	main	withdraw_package_profit	2024-12-04 16:29:03	\N	2024-12-04 16:29:03	2024-12-04 16:29:03
673	WPP-78WeXaHNPm	149.40059038	4501	main	withdraw_package_profit	2024-12-04 16:29:06	\N	2024-12-04 16:29:06	2024-12-04 16:29:06
674	HD-4PoeNFxcQB	532.00000000	20	main	hidden_deposit	2024-12-06 06:19:02	\N	2024-12-06 06:19:02	2024-12-06 06:19:02
675	WPP-SNXl3CAMmJ	2.20866814	3	main	withdraw_package_profit	2024-12-06 12:23:23	\N	2024-12-06 12:23:23	2024-12-06 12:23:23
676	WPP-hDs93Rcs6w	4.86068678	3	main	withdraw_package_profit	2024-12-06 12:23:27	\N	2024-12-06 12:23:27	2024-12-06 12:23:27
681	WPP-tobQqzQ28Q	45.88029461	4490	main	withdraw_package_profit	2024-12-08 18:01:59	\N	2024-12-08 18:01:59	2024-12-08 18:01:59
682	WPP-sJNMAvj5Ai	68.29453198	4490	main	withdraw_package_profit	2024-12-08 18:02:13	\N	2024-12-08 18:02:13	2024-12-08 18:02:13
683	WPP-YGsn9EDylK	28.30256944	4494	main	withdraw_package_profit	2024-12-08 18:03:25	\N	2024-12-08 18:03:25	2024-12-08 18:03:25
684	WPP-EsklZ9gqjZ	24.01356501	4494	main	withdraw_package_profit	2024-12-08 18:03:29	\N	2024-12-08 18:03:29	2024-12-08 18:03:29
685	WP-mruEJqc75P	24.00000000	4494	main	withdraw	2024-12-08 19:38:06	\N	2024-12-08 18:04:20	2024-12-08 19:38:06
680	WP-pMKMBG3OFw	19.00000000	4495	main	withdraw	2024-12-08 19:38:07	\N	2024-12-08 18:00:02	2024-12-08 19:38:07
677	WP-Fm8EavJLC9	28.65000000	5	main	withdraw	2024-12-08 19:48:02	\N	2024-12-08 04:03:07	2024-12-08 19:48:02
679	WP-Fh5rtcNxSA	300.00000000	8	main	withdraw	2024-12-08 19:55:05	\N	2024-12-08 15:38:46	2024-12-08 19:55:05
686	HD-0silycoj5s	-100.00000000	4497	main	hidden_deposit	2024-12-08 19:57:10	\N	2024-12-08 19:57:10	2024-12-08 19:57:10
687	HD-k0nWDJQgMc	-100.00000000	4470	main	hidden_deposit	2024-12-08 19:58:42	\N	2024-12-08 19:58:42	2024-12-08 19:58:42
688	HD-SUjhueOfKe	-100.00000000	15	main	hidden_deposit	2024-12-08 19:59:25	\N	2024-12-08 19:59:25	2024-12-08 19:59:25
689	WPP-ZwW1iscpoO	26.19309776	4485	main	withdraw_package_profit	2024-12-09 15:21:45	\N	2024-12-09 15:21:45	2024-12-09 15:21:45
690	WPP-qPks5hfWS0	13.31833512	4485	main	withdraw_package_profit	2024-12-09 15:21:49	\N	2024-12-09 15:21:49	2024-12-09 15:21:49
691	WPP-TDz8fMM5zX	1.85161200	4485	main	withdraw_package_profit	2024-12-09 15:21:51	\N	2024-12-09 15:21:51	2024-12-09 15:21:51
692	WPP-Es9ccusY9c	28.49876027	4485	main	withdraw_package_profit	2024-12-09 15:21:56	\N	2024-12-09 15:21:56	2024-12-09 15:21:56
693	DP-GrSepB0UIK	982.00000000	45	main	deposit	2024-12-09 16:07:41	\N	2024-12-09 16:06:42	2024-12-09 16:07:41
632	ITC-oMN46k7kan	200.00000000	34	main	buy_package	2024-11-28 18:29:22	\N	2024-11-28 18:29:22	2024-12-23 07:58:59
695	WPP-2f1GBwaRxf	0.38635564	8	main	withdraw_package_profit	2024-12-09 16:24:07	\N	2024-12-09 16:24:07	2024-12-09 16:24:07
696	WPP-iYVyURtvN0	31.09411714	8	main	withdraw_package_profit	2024-12-09 16:24:15	\N	2024-12-09 16:24:15	2024-12-09 16:24:15
697	WPP-pNPUoNL4XH	19.89965541	8	main	withdraw_package_profit	2024-12-09 16:24:33	\N	2024-12-09 16:24:33	2024-12-09 16:24:33
678	WP-Wm6dBmW4gB	40.00000000	39	main	withdraw	2024-12-09 17:04:32	\N	2024-12-08 07:22:56	2024-12-09 17:04:32
698	HD-8nYuRP3aaM	880.00000000	21	main	hidden_deposit	2024-12-09 17:06:32	\N	2024-12-09 17:06:32	2024-12-09 17:06:32
699	WPP-Zcp0TZyOXJ	2.33772365	5	main	withdraw_package_profit	2024-12-09 17:34:14	\N	2024-12-09 17:34:14	2024-12-09 17:34:14
700	WPP-950fNBDQPO	7.83975531	5	main	withdraw_package_profit	2024-12-09 17:34:17	\N	2024-12-09 17:34:17	2024-12-09 17:34:17
701	WPP-OyHxjzwqkr	2.06940094	5	main	withdraw_package_profit	2024-12-09 17:34:19	\N	2024-12-09 17:34:19	2024-12-09 17:34:19
702	WPP-owrQ66foCw	1.85161200	5	main	withdraw_package_profit	2024-12-09 17:34:22	\N	2024-12-09 17:34:22	2024-12-09 17:34:22
703	HD-Oo96HDoTpK	-484.27710778	4501	main	hidden_deposit	2024-12-09 18:13:46	\N	2024-12-09 18:13:46	2024-12-09 18:13:46
704	WPP-wWbmMh6U3t	2.14433800	3	main	withdraw_package_profit	2024-12-10 08:22:03	\N	2024-12-10 08:22:03	2024-12-10 08:22:03
705	WPP-9X5WP0IkrU	4.71911338	3	main	withdraw_package_profit	2024-12-10 08:22:06	\N	2024-12-10 08:22:06	2024-12-10 08:22:06
706	DP-rhSWGw5n7I	2420.00000000	46	main	deposit	2024-12-10 11:25:49	\N	2024-12-10 11:07:20	2024-12-10 11:25:49
707	ITC-S6dbIk79cE	2420.00000000	46	main	buy_package	2024-12-10 11:31:56	\N	2024-12-10 11:31:56	2024-12-10 11:31:56
708	HD-5O5CUo0L1R	110.00000000	38	main	hidden_deposit	2024-12-10 12:01:32	\N	2024-12-10 12:01:32	2024-12-10 12:01:32
709	WPP-l6yrARKI9o	296.93787812	4501	main	withdraw_package_profit	2024-12-15 12:13:43	\N	2024-12-15 12:13:43	2024-12-15 12:13:43
710	WPP-1tOhL6nyFH	12.92516883	4501	main	withdraw_package_profit	2024-12-15 12:13:56	\N	2024-12-15 12:13:56	2024-12-15 12:13:56
711	WPP-iANIr8yG3J	14.48578406	4501	main	withdraw_package_profit	2024-12-15 12:13:59	\N	2024-12-15 12:13:59	2024-12-15 12:13:59
712	WPP-ftgsF8H7ob	145.04911687	4501	main	withdraw_package_profit	2024-12-15 12:14:05	\N	2024-12-15 12:14:05	2024-12-15 12:14:05
714	WPP-tOiL7uYcwP	39.06242478	4464	main	withdraw_package_profit	2024-12-15 14:34:49	\N	2024-12-15 14:34:49	2024-12-15 14:34:49
715	WPP-NBbU0tA0vy	31.20103195	4464	main	withdraw_package_profit	2024-12-15 14:34:52	\N	2024-12-15 14:34:52	2024-12-15 14:34:52
717	WPP-65C6cwus1D	207.20722793	36	main	withdraw_package_profit	2024-12-15 15:38:16	\N	2024-12-15 15:38:16	2024-12-15 15:38:16
716	WP-HdBDONFbOF	70.00000000	4464	main	withdraw	2024-12-15 15:45:23	\N	2024-12-15 14:35:12	2024-12-15 15:45:23
747	WPP-sEpg9Tl4HN	145.04911687	4501	main	withdraw_package_profit	2024-12-17 07:49:50	\N	2024-12-17 07:49:50	2024-12-17 07:49:50
718	WP-0K0sXtjIIs	207.00000000	36	main	withdraw	2024-12-15 15:54:24	\N	2024-12-15 15:39:47	2024-12-15 15:54:24
719	WPP-e7hnHcJMkA	104.74111522	18	main	withdraw_package_profit	2024-12-15 15:57:04	\N	2024-12-15 15:57:04	2024-12-15 15:57:04
720	WP-L0gIxSeMLs	105.00000000	18	main	withdraw	2024-12-15 16:11:50	\N	2024-12-15 16:08:53	2024-12-15 16:11:50
721	WPP-DKkDecJS1t	1.95549455	4490	main	withdraw_package_profit	2024-12-15 17:16:02	\N	2024-12-15 17:16:02	2024-12-15 17:16:02
722	WPP-uR3EKSTEXu	33.64262659	4490	main	withdraw_package_profit	2024-12-15 17:17:19	\N	2024-12-15 17:17:19	2024-12-15 17:17:19
723	WPP-ZV5cwTlc6h	22.60113035	4490	main	withdraw_package_profit	2024-12-15 17:17:22	\N	2024-12-15 17:17:22	2024-12-15 17:17:22
724	WPP-WgGqtAMHfW	13.94215243	4494	main	withdraw_package_profit	2024-12-15 17:18:15	\N	2024-12-15 17:18:15	2024-12-15 17:18:15
725	WPP-2rTtRE8jvU	11.82934237	4494	main	withdraw_package_profit	2024-12-15 17:18:18	\N	2024-12-15 17:18:18	2024-12-15 17:18:18
726	WPP-AlkfBwbWxR	109.20006370	4505	main	withdraw_package_profit	2024-12-15 18:54:36	\N	2024-12-15 18:54:36	2024-12-15 18:54:36
728	WPP-fJazzD1tYz	182.00020020	4497	main	withdraw_package_profit	2024-12-15 18:57:41	\N	2024-12-15 18:57:41	2024-12-15 18:57:41
730	WPP-dpXRXr5Wi1	74.69458356	4503	main	withdraw_package_profit	2024-12-15 19:02:19	\N	2024-12-15 19:02:19	2024-12-15 19:02:19
732	WPP-l7KCjBaYUl	109.20006370	4508	main	withdraw_package_profit	2024-12-15 19:05:27	\N	2024-12-15 19:05:27	2024-12-15 19:05:27
734	WP-A18SRmV6bc	14.11000000	5	main	withdraw	2024-12-16 04:00:15	\N	2024-12-15 21:08:38	2024-12-16 04:00:15
735	WPP-aGa3PTSo5W	130.61788562	4459	main	withdraw_package_profit	2024-12-16 06:50:48	\N	2024-12-16 06:50:48	2024-12-16 06:50:48
736	WPP-J09xcHs53E	266.58223262	4459	main	withdraw_package_profit	2024-12-16 06:50:52	\N	2024-12-16 06:50:52	2024-12-16 06:50:52
737	WPP-j1DHnz362R	9.31360836	4459	main	withdraw_package_profit	2024-12-16 06:50:55	\N	2024-12-16 06:50:55	2024-12-16 06:50:55
738	WPP-Ty9HZbDidO	60.07403781	4459	main	withdraw_package_profit	2024-12-16 06:50:59	\N	2024-12-16 06:50:59	2024-12-16 06:50:59
739	WPP-lDSA7d4AwV	4.71911338	3	main	withdraw_package_profit	2024-12-16 10:39:47	\N	2024-12-16 10:39:47	2024-12-16 10:39:47
740	WPP-AN7oGTgJY0	2.14433800	3	main	withdraw_package_profit	2024-12-16 10:39:50	\N	2024-12-16 10:39:50	2024-12-16 10:39:50
741	WPP-OogzKfgU7a	0.38635564	8	main	withdraw_package_profit	2024-12-16 11:27:47	\N	2024-12-16 11:27:47	2024-12-16 11:27:47
742	WPP-OuHfQszVVS	31.09411714	8	main	withdraw_package_profit	2024-12-16 11:27:54	\N	2024-12-16 11:27:54	2024-12-16 11:27:54
743	WPP-UicI6lULyA	19.89965541	8	main	withdraw_package_profit	2024-12-16 11:28:04	\N	2024-12-16 11:28:04	2024-12-16 11:28:04
744	WPP-ZhmGFmr3py	296.93787812	4501	main	withdraw_package_profit	2024-12-17 07:49:35	\N	2024-12-17 07:49:35	2024-12-17 07:49:35
745	WPP-7uDZqVujhI	12.92516883	4501	main	withdraw_package_profit	2024-12-17 07:49:39	\N	2024-12-17 07:49:39	2024-12-17 07:49:39
746	WPP-0ZPL09ZyFV	14.48578406	4501	main	withdraw_package_profit	2024-12-17 07:49:42	\N	2024-12-17 07:49:42	2024-12-17 07:49:42
733	WP-AWAuN5q1aH	110.00000000	4508	main	withdraw	2024-12-17 08:51:21	\N	2024-12-15 19:06:28	2024-12-17 08:51:21
731	WP-QNaQ6Nuj31	74.00000000	4503	main	withdraw	2024-12-17 08:51:22	\N	2024-12-15 19:03:22	2024-12-17 08:51:22
729	WP-DuPderBkxD	182.00000000	4497	main	withdraw	2024-12-17 08:51:23	\N	2024-12-15 19:01:07	2024-12-17 08:51:23
727	WP-eDDZCFcILy	109.00000000	4505	main	withdraw	2024-12-17 08:51:23	\N	2024-12-15 18:56:19	2024-12-17 08:51:23
748	HD-LPBEGDO3gC	970.00000000	38	main	hidden_deposit	2024-12-17 12:14:00	\N	2024-12-17 12:14:00	2024-12-17 12:14:00
749	HD-NXzaT6iNQb	2430.00000000	20	main	hidden_deposit	2024-12-17 16:47:21	\N	2024-12-17 16:47:21	2024-12-17 16:47:21
713	WP-SS4OlRflKm	469.00000000	4501	main	withdraw	2024-12-17 17:21:20	\N	2024-12-15 12:18:33	2024-12-17 17:21:20
750	HD-XTesuMm1Dn	1435.00000000	20	main	hidden_deposit	2024-12-18 10:28:19	\N	2024-12-18 10:28:19	2024-12-18 10:28:19
751	HD-vtV3aZdljg	195.00000000	20	main	hidden_deposit	2024-12-18 11:05:51	\N	2024-12-18 11:05:51	2024-12-18 11:05:51
752	WPP-nlrNWkkgrR	26.19309776	4485	main	withdraw_package_profit	2024-12-20 16:13:26	\N	2024-12-20 16:13:26	2024-12-20 16:13:26
753	WPP-rRWCIlrMQo	13.31833512	4485	main	withdraw_package_profit	2024-12-20 16:13:29	\N	2024-12-20 16:13:29	2024-12-20 16:13:29
754	WPP-i1PysVEhO6	1.85161200	4485	main	withdraw_package_profit	2024-12-20 16:13:31	\N	2024-12-20 16:13:31	2024-12-20 16:13:31
755	WPP-uuLCEKiGLv	28.49876027	4485	main	withdraw_package_profit	2024-12-20 16:13:34	\N	2024-12-20 16:13:34	2024-12-20 16:13:34
756	HD-UwNrfsnRQm	1518.00000000	45	main	hidden_deposit	2024-12-21 18:16:58	\N	2024-12-21 18:16:58	2024-12-21 18:16:58
757	HD-lwGIPesU6g	75.00000000	4501	partner	hidden_deposit	2024-12-21 18:24:17	\N	2024-12-21 18:24:17	2024-12-21 18:24:17
760	WP-jFJNYFa58F	42.00000000	4470	main	withdraw	2024-12-22 09:04:30	\N	2024-12-22 07:58:51	2024-12-22 09:04:30
759	WP-Tg5H8ozuTg	467.00000000	4459	main	withdraw	2024-12-22 09:45:52	\N	2024-12-22 05:47:00	2024-12-22 09:45:52
758	WP-dFFTJSI5Ip	469.00000000	4501	main	withdraw	2024-12-22 12:33:12	\N	2024-12-22 03:43:50	2024-12-22 12:33:12
762	WPP-hEOBQs73An	33.64262659	4490	main	withdraw_package_profit	2024-12-22 14:24:17	\N	2024-12-22 14:24:17	2024-12-22 14:24:17
763	WPP-Re6XBi9i5R	1.95549455	4490	main	withdraw_package_profit	2024-12-22 14:24:26	\N	2024-12-22 14:24:26	2024-12-22 14:24:26
764	WPP-bWib8YnKex	22.60113035	4490	main	withdraw_package_profit	2024-12-22 14:24:40	\N	2024-12-22 14:24:40	2024-12-22 14:24:40
765	WPP-oqNfP0mt6Q	13.94215243	4494	main	withdraw_package_profit	2024-12-22 14:25:21	\N	2024-12-22 14:25:21	2024-12-22 14:25:21
766	WPP-QtvudWobYR	11.82934237	4494	main	withdraw_package_profit	2024-12-22 14:25:24	\N	2024-12-22 14:25:24	2024-12-22 14:25:24
768	WPP-T8ngC2unn2	2.33772365	5	main	withdraw_package_profit	2024-12-22 15:07:32	\N	2024-12-22 15:07:32	2024-12-22 15:07:32
769	WPP-Y80vRwDSt6	7.83975531	5	main	withdraw_package_profit	2024-12-22 15:07:34	\N	2024-12-22 15:07:34	2024-12-22 15:07:34
770	WPP-JtJcBAec8T	2.06940094	5	main	withdraw_package_profit	2024-12-22 15:07:37	\N	2024-12-22 15:07:37	2024-12-22 15:07:37
771	WPP-q01vzsDgH4	1.85161200	5	main	withdraw_package_profit	2024-12-22 15:07:40	\N	2024-12-22 15:07:40	2024-12-22 15:07:40
767	WP-dQSGLDLrOK	80.00000000	4494	main	withdraw	2024-12-22 15:09:49	\N	2024-12-22 14:26:56	2024-12-22 15:09:49
761	WP-yP9T6DJyC0	215.00000000	4485	main	withdraw	2024-12-22 15:10:55	\N	2024-12-22 11:57:14	2024-12-22 15:10:55
772	WP-ySQMxaOJ6o	231.00000000	4490	main	withdraw	2024-12-22 17:56:56	\N	2024-12-22 16:21:47	2024-12-22 17:56:56
773	WPP-n513uU32Zb	54.66248906	4471	main	withdraw_package_profit	2024-12-22 19:53:20	\N	2024-12-22 19:53:20	2024-12-22 19:53:20
774	WPP-4mafZr2L7o	663.03835694	4471	main	withdraw_package_profit	2024-12-22 19:54:24	\N	2024-12-22 19:54:24	2024-12-22 19:54:24
776	HD-m0woEBrAOl	-100.00000000	4459	main	hidden_deposit	2024-12-23 07:33:33	\N	2024-12-23 07:33:33	2024-12-23 07:33:33
777	HD-qdaK5164Hs	-100.00000000	4470	main	hidden_deposit	2024-12-23 07:34:31	\N	2024-12-23 07:34:31	2024-12-23 07:34:31
778	HD-YnRnt4QhxA	-100.00000000	14	main	hidden_deposit	2024-12-23 07:36:02	\N	2024-12-23 07:36:02	2024-12-23 07:36:02
779	HD-OEZJnwS2jc	100.00000000	34	main	hidden_deposit	2024-12-23 07:59:10	\N	2024-12-23 07:59:10	2024-12-23 07:59:10
780	WPP-hSpxI3qL7G	4.71911338	3	main	withdraw_package_profit	2024-12-23 09:56:39	\N	2024-12-23 09:56:39	2024-12-23 09:56:39
781	WPP-KeljEbZ9qx	2.14433800	3	main	withdraw_package_profit	2024-12-23 09:56:42	\N	2024-12-23 09:56:42	2024-12-23 09:56:42
782	WPP-wMSPkL17tA	115.07558804	20	main	withdraw_package_profit	2024-12-23 10:23:55	\N	2024-12-23 10:23:55	2024-12-23 10:23:55
783	WPP-mRanara8DC	0.38635564	8	main	withdraw_package_profit	2024-12-23 12:37:00	\N	2024-12-23 12:37:00	2024-12-23 12:37:00
784	WPP-S6gQIuyDaf	31.09411714	8	main	withdraw_package_profit	2024-12-23 12:37:07	\N	2024-12-23 12:37:07	2024-12-23 12:37:07
785	WPP-SKcZULOwhM	19.89965541	8	main	withdraw_package_profit	2024-12-23 12:37:19	\N	2024-12-23 12:37:19	2024-12-23 12:37:19
786	DP-E7XpLL9aqM	1000.00000000	49	main	deposit	2024-12-23 15:43:45	\N	2024-12-23 15:43:10	2024-12-23 15:43:45
788	HD-pyC6PCpUwd	1500.00000000	49	main	hidden_deposit	2024-12-23 17:21:26	\N	2024-12-23 17:21:26	2024-12-23 17:21:26
801	WP-Tj6AzE233C	70.00000000	4504	main	withdraw	2024-12-29 14:40:12	\N	2024-12-29 09:53:03	2024-12-29 14:40:12
789	HD-jkdslKFiCt	100.00000000	14	main	hidden_deposit	2024-12-23 18:54:42	\N	2024-12-23 18:54:42	2024-12-23 18:54:42
355	ITC-m7i1VBxXNm	100.00000000	14	main	buy_package	2024-10-16 17:02:18	\N	2024-10-16 17:02:18	2024-12-23 18:54:59
790	HD-GiAaxFNXDA	965.00000000	38	main	hidden_deposit	2024-12-24 05:32:17	\N	2024-12-24 05:32:17	2024-12-24 05:32:17
775	WP-tJ13saexJF	717.00000000	4471	main	withdraw	2024-12-24 09:13:33	\N	2024-12-22 20:05:32	2024-12-24 09:13:33
791	HD-Qr2hdEsAWw	1000.00000000	50	main	hidden_deposit	2024-12-25 15:02:36	\N	2024-12-25 15:02:36	2024-12-25 15:02:36
792	ITC-iSV1wESOKD	1000.00000000	50	main	buy_package	2024-12-25 15:07:59	\N	2024-12-25 15:07:59	2024-12-25 15:07:59
793	HD-xb9WOaVVWh	-75.00000000	4501	partner	hidden_deposit	2024-12-27 08:29:58	\N	2024-12-27 08:29:58	2024-12-27 08:29:58
794	WPP-EuD1teAPvU	2.33772365	5	main	withdraw_package_profit	2024-12-27 20:46:22	\N	2024-12-27 20:46:22	2024-12-27 20:46:22
795	WPP-E8OUeUrrgh	7.83975531	5	main	withdraw_package_profit	2024-12-27 20:46:24	\N	2024-12-27 20:46:24	2024-12-27 20:46:24
796	WPP-4SQ5NRCMx8	2.06940094	5	main	withdraw_package_profit	2024-12-27 20:46:27	\N	2024-12-27 20:46:27	2024-12-27 20:46:27
797	WPP-3NloEk9JWl	1.85161200	5	main	withdraw_package_profit	2024-12-27 20:46:30	\N	2024-12-27 20:46:30	2024-12-27 20:46:30
798	HD-zxG7nh3heo	965.00000000	38	main	hidden_deposit	2024-12-28 10:57:40	\N	2024-12-28 10:57:40	2024-12-28 10:57:40
800	WPP-FPM7lhOiux	70.92712714	4504	main	withdraw_package_profit	2024-12-29 06:22:36	\N	2024-12-29 06:22:36	2024-12-29 06:22:36
799	WP-qmcxyLH7Ey	115.00000000	20	main	withdraw	2024-12-29 09:09:38	\N	2024-12-29 06:18:19	2024-12-29 09:09:38
803	WPP-6Cw52cLXp0	65.16781362	39	main	withdraw_package_profit	2024-12-29 11:10:03	\N	2024-12-29 11:10:03	2024-12-29 11:10:03
805	WPP-15YapoOycp	40.12089110	4464	main	withdraw_package_profit	2024-12-29 11:12:33	\N	2024-12-29 11:12:33	2024-12-29 11:12:33
806	WPP-p6cc7v2GMJ	31.90557216	4464	main	withdraw_package_profit	2024-12-29 11:12:36	\N	2024-12-29 11:12:36	2024-12-29 11:12:36
810	DP-PQuuRXvAAp	92.00000000	52	main	deposit	2024-12-29 13:46:51	\N	2024-12-29 13:42:59	2024-12-29 13:46:51
811	HD-9bS6VSTenq	8.00000000	52	main	hidden_deposit	2024-12-29 13:50:03	\N	2024-12-29 13:50:03	2024-12-29 13:50:03
812	ITC-urP93T9Md3	100.00000000	52	main	buy_package	2024-12-29 13:51:48	\N	2024-12-29 13:51:48	2024-12-29 13:51:48
813	HD-UnUWvyfEcY	2500.00000000	49	main	hidden_deposit	2024-12-29 13:53:05	\N	2024-12-29 13:53:05	2024-12-29 13:53:05
694	ITC-gKLELcDX6G	3930.00000000	45	main	buy_package	2024-12-09 16:09:06	\N	2024-12-09 16:09:06	2024-12-31 15:20:37
802	WP-1YVHWUEloS	36.90000000	3	main	withdraw	2024-12-29 14:41:48	\N	2024-12-29 10:20:42	2024-12-29 14:41:48
809	WP-wUo59pZh8f	28.20000000	5	main	withdraw	2024-12-29 14:42:45	\N	2024-12-29 12:53:13	2024-12-29 14:42:45
807	WP-cB3OGtkztw	72.00000000	4464	main	withdraw	2024-12-29 14:43:51	\N	2024-12-29 11:13:19	2024-12-29 14:43:51
808	WP-NJKy4kDZMq	154.00000000	8	main	withdraw	2024-12-29 14:45:14	\N	2024-12-29 12:52:36	2024-12-29 14:45:14
804	WP-aPZBUgNLEf	69.00000000	39	main	withdraw	2024-12-29 14:54:39	\N	2024-12-29 11:11:15	2024-12-29 14:54:39
814	HD-AodgQaX88F	3.00000000	8	partner	hidden_deposit	2024-12-29 16:19:47	\N	2024-12-29 16:19:47	2024-12-29 16:19:47
815	WPP-nvgbFoHAzt	115.07558804	20	main	withdraw_package_profit	2024-12-30 06:10:54	\N	2024-12-30 06:10:54	2024-12-30 06:10:54
816	HD-st8wfiVX1G	1000.00000000	19	main	hidden_deposit	2024-12-30 08:56:51	\N	2024-12-30 08:56:51	2024-12-30 08:56:51
817	WPP-baxoRqBGEK	0.38635564	8	main	withdraw_package_profit	2024-12-30 10:06:38	\N	2024-12-30 10:06:38	2024-12-30 10:06:38
818	WPP-4B7Zhx4F9I	31.09411714	8	main	withdraw_package_profit	2024-12-30 10:06:43	\N	2024-12-30 10:06:43	2024-12-30 10:06:43
819	WPP-zwBnjsGBcw	19.89965541	8	main	withdraw_package_profit	2024-12-30 10:06:47	\N	2024-12-30 10:06:47	2024-12-30 10:06:47
820	WPP-rhuB1a5tys	1.88589667	4485	main	withdraw_package_profit	2024-12-30 14:53:54	\N	2024-12-30 14:53:54	2024-12-30 14:53:54
824	DP-0hnKYuqt6F	300.00000000	54	main	deposit	2024-12-30 15:43:37	\N	2024-12-30 15:41:58	2024-12-30 15:43:37
823	DP-wRzT1THkIc	100.00000000	57	main	deposit	2024-12-30 15:43:38	\N	2024-12-30 15:41:01	2024-12-30 15:43:38
822	DP-qoLDtvif2N	100.00000000	55	main	deposit	2024-12-30 15:43:39	\N	2024-12-30 15:35:56	2024-12-30 15:43:39
821	DP-VRFRSbiTol	500.00000000	53	main	deposit	2024-12-30 15:43:40	\N	2024-12-30 15:33:30	2024-12-30 15:43:40
826	ITC-m39FWedy91	300.00000000	54	main	buy_package	2024-12-30 15:45:27	\N	2024-12-30 15:45:27	2024-12-30 15:45:27
827	ITC-2invasa2qG	100.00000000	57	main	buy_package	2024-12-30 15:46:06	\N	2024-12-30 15:46:06	2024-12-30 15:46:06
828	ITC-21gDyB05pj	100.00000000	55	main	buy_package	2024-12-30 15:46:52	\N	2024-12-30 15:46:52	2024-12-30 15:46:52
829	HD-7cNiXuqkXJ	70.00000000	38	partner	hidden_deposit	2024-12-30 15:51:04	\N	2024-12-30 15:51:04	2024-12-30 15:51:04
830	HD-StUe01ZvGy	35.00000000	53	main	hidden_deposit	2024-12-30 15:52:45	\N	2024-12-30 15:52:45	2024-12-30 15:52:45
825	ITC-60gEs0t6rc	535.00000000	53	main	buy_package	2024-12-30 15:44:56	\N	2024-12-30 15:44:56	2024-12-30 15:52:58
831	HD-Y6uh8eNlyM	5000.00000000	49	main	hidden_deposit	2024-12-30 18:26:40	\N	2024-12-30 18:26:40	2024-12-30 18:26:40
787	ITC-uzsAOBkQp1	10000.00000000	49	main	buy_package	2024-12-23 15:44:08	\N	2024-12-23 15:44:08	2024-12-30 18:26:59
832	WPP-Wi1smOxM9Y	2.33772365	5	main	withdraw_package_profit	2024-12-31 14:30:55	\N	2024-12-31 14:30:55	2024-12-31 14:30:55
833	WPP-HC8iav3jcY	7.83975531	5	main	withdraw_package_profit	2024-12-31 14:30:57	\N	2024-12-31 14:30:57	2024-12-31 14:30:57
834	WPP-gr4tD7gw2C	2.06940094	5	main	withdraw_package_profit	2024-12-31 14:31:00	\N	2024-12-31 14:31:00	2024-12-31 14:31:00
835	WPP-W2k0hVUjjl	1.85161200	5	main	withdraw_package_profit	2024-12-31 14:31:03	\N	2024-12-31 14:31:03	2024-12-31 14:31:03
836	HD-47RWrTyY6T	1430.00000000	45	main	hidden_deposit	2024-12-31 15:20:09	\N	2024-12-31 15:20:09	2024-12-31 15:20:09
837	HD-w4ITAgNRNv	71.00000000	4501	partner	hidden_deposit	2024-12-31 15:21:20	\N	2024-12-31 15:21:20	2024-12-31 15:21:20
838	DP-Ih97h6izLA	10500.00000000	59	main	deposit	\N	2025-01-02 17:33:49	2025-01-02 17:29:47	2025-01-02 17:33:49
839	DP-2AYR0YtlVo	100.00000000	59	main	deposit	2025-01-02 17:38:21	\N	2025-01-02 17:37:55	2025-01-02 17:38:21
840	ITC-PRsIBpFHlW	100.00000000	59	main	buy_package	2025-01-02 17:39:43	\N	2025-01-02 17:39:43	2025-01-02 17:39:43
841	WPP-GbxTHRGqE0	251.43711662	4493	main	withdraw_package_profit	2025-01-04 16:00:06	\N	2025-01-04 16:00:06	2025-01-04 16:00:06
842	WP-FfQbj07awf	115.00000000	20	main	withdraw	2025-01-05 06:28:17	\N	2025-01-05 01:33:53	2025-01-05 06:28:17
843	WPP-GrpICAojf2	112.90335000	49	main	withdraw_package_profit	2025-01-05 13:59:21	\N	2025-01-05 13:59:21	2025-01-05 13:59:21
845	WPP-5sB5EsBxx9	3.91098910	4490	main	withdraw_package_profit	2025-01-05 16:31:24	\N	2025-01-05 16:31:24	2025-01-05 16:31:24
846	WPP-DqiXFjPDTs	67.28525318	4490	main	withdraw_package_profit	2025-01-05 16:34:16	\N	2025-01-05 16:34:16	2025-01-05 16:34:16
847	WPP-8pWoidQus8	45.20226070	4490	main	withdraw_package_profit	2025-01-05 16:34:21	\N	2025-01-05 16:34:21	2025-01-05 16:34:21
848	WPP-RnyLzdYSFK	27.88430486	4494	main	withdraw_package_profit	2025-01-05 16:35:55	\N	2025-01-05 16:35:55	2025-01-05 16:35:55
849	WPP-4KYmQPkuwr	23.65868474	4494	main	withdraw_package_profit	2025-01-05 16:35:57	\N	2025-01-05 16:35:57	2025-01-05 16:35:57
865	HD-sqU1Se2jU0	500.00000000	34	main	hidden_deposit	2025-01-06 09:29:09	\N	2025-01-06 09:29:09	2025-01-06 09:29:09
844	WP-suFVpYJVlJ	110.00000000	49	main	withdraw	2025-01-05 17:23:46	\N	2025-01-05 14:01:08	2025-01-05 17:23:46
850	WPP-2OozL1YR5u	55.60390836	4503	main	withdraw_package_profit	2025-01-05 18:30:30	\N	2025-01-05 18:30:30	2025-01-05 18:30:30
852	WPP-iu9oNLSGYt	135.48402000	4497	main	withdraw_package_profit	2025-01-05 18:34:39	\N	2025-01-05 18:34:39	2025-01-05 18:34:39
854	WPP-EyaBVzsMkt	81.29037000	4505	main	withdraw_package_profit	2025-01-05 18:38:22	\N	2025-01-05 18:38:22	2025-01-05 18:38:22
856	WPP-XJBp5vXGcH	81.29037000	4508	main	withdraw_package_profit	2025-01-05 18:55:36	\N	2025-01-05 18:55:36	2025-01-05 18:55:36
857	WP-k9V3OVGHdv	81.00000000	4508	main	withdraw	2025-01-05 19:07:51	\N	2025-01-05 18:56:40	2025-01-05 19:07:51
855	WP-Coki5s794b	82.00000000	4505	main	withdraw	2025-01-05 19:07:52	\N	2025-01-05 18:39:21	2025-01-05 19:07:52
853	WP-uEalgudwVN	136.00000000	4497	main	withdraw	2025-01-05 19:07:53	\N	2025-01-05 18:35:57	2025-01-05 19:07:53
851	WP-KX8BSh80Ze	55.00000000	4503	main	withdraw	2025-01-05 19:07:54	\N	2025-01-05 18:32:03	2025-01-05 19:07:54
858	HD-2RsEYZ0ctn	4.00000000	53	partner	hidden_deposit	2025-01-05 19:25:55	\N	2025-01-05 19:25:55	2025-01-05 19:25:55
859	HD-UqANacdy9R	1.00000000	38	partner	hidden_deposit	2025-01-05 19:27:25	\N	2025-01-05 19:27:25	2025-01-05 19:27:25
860	WPP-mthHthcM4B	43.06210689	4470	main	withdraw_package_profit	2025-01-06 05:52:46	\N	2025-01-06 05:52:46	2025-01-06 05:52:46
861	WPP-5HUxdHRMp2	0.81074713	4470	main	withdraw_package_profit	2025-01-06 05:52:51	\N	2025-01-06 05:52:51	2025-01-06 05:52:51
862	WPP-qxG2rEHGZw	0.38635564	8	main	withdraw_package_profit	2025-01-06 09:17:17	\N	2025-01-06 09:17:17	2025-01-06 09:17:17
863	WPP-WM2LDAaZe1	31.09411714	8	main	withdraw_package_profit	2025-01-06 09:17:25	\N	2025-01-06 09:17:25	2025-01-06 09:17:25
864	WPP-qo1jA2OCcU	19.89965541	8	main	withdraw_package_profit	2025-01-06 09:17:31	\N	2025-01-06 09:17:31	2025-01-06 09:17:31
866	WPP-T6gyCNHOLJ	27.38936867	4485	main	withdraw_package_profit	2025-01-06 14:17:26	\N	2025-01-06 14:17:26	2025-01-06 14:17:26
867	WPP-w7d9aaObgQ	13.92659983	4485	main	withdraw_package_profit	2025-01-06 14:17:28	\N	2025-01-06 14:17:28	2025-01-06 14:17:28
868	WPP-bWhdY2peCw	1.88589667	4485	main	withdraw_package_profit	2025-01-06 14:17:30	\N	2025-01-06 14:17:30	2025-01-06 14:17:30
869	WPP-hsRjS7avce	29.80033361	4485	main	withdraw_package_profit	2025-01-06 14:17:32	\N	2025-01-06 14:17:32	2025-01-06 14:17:32
870	WPP-tT3lyrEDqK	115.07558804	20	main	withdraw_package_profit	2025-01-06 17:16:00	\N	2025-01-06 17:16:00	2025-01-06 17:16:00
871	HD-AChP6g9AjR	1000.00000000	61	main	hidden_deposit	2025-01-07 08:27:34	\N	2025-01-07 08:27:34	2025-01-07 08:27:34
872	ITC-KJkeQhMidw	1000.00000000	61	main	buy_package	2025-01-07 08:27:52	\N	2025-01-07 08:27:52	2025-01-07 08:27:52
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, first_name, last_name, username, email, email_verified_at, password, remember_token, created_at, updated_at, banned_at, rank) FROM stdin;
39	Ludmila	Tuchina	Ludmila	ludmilatuchina1973@icloud.com	\N	$2y$12$ZY5xXm3VtJs8aZvg2t5RDup5jXjvBAUkpYdqBkXQVXInYuPZBZzz2	Y0BTFJNids6kN6sJ57ZOgDIyDtM1zlFeaEHkrCTEkWnHdjOWUq2FI1SHnx7z	2024-11-23 12:24:23	2024-11-23 12:24:23	\N	1
4486	Ivan	Lobzin	Ivan157	timecps1@yandex.ru	\N	$2b$07$MLJOK3Yb.7qTLGaSiN4sEulZFRaqCCx5qMssVGoGPHqUUcxB7AFau	\N	2024-08-20 10:00:00	2024-09-17 09:27:42	2024-09-17 09:27:42	1
4487	qweqwe	qweqwe	bulat	0301103106@edu.tatar.ru	\N	$2b$07$8eS75E9gHFxdSrimZvyl9.dR5KcfHd9PeejhNXGqwx9faL5I0YIve	\N	2024-08-20 10:00:00	2024-09-17 09:28:17	2024-09-17 09:28:17	1
4463	Живайкин	Александрович	СергейЖив7	seregakasper5@gmail.com	\N	$2b$07$pugnjTdvjMZPliGzOyufk.UhUFXwW5cxH0p6TFY30fvPLTCBAyGfS	\N	2024-08-20 10:00:00	2024-09-17 09:29:48	2024-09-17 09:29:48	1
11	Жанна	Кузнецова 	zhanna_smith	zhannakuznecova2000@gmail.com	\N	$2y$12$sHzwinpAUrfp/kxNB2GqXe1WxWhimGSkjVY6M3GFUzI3zslCYfZlq	nGO604buf3jeq8cK6nQFufvCouEgH4vTPmFpBFA8jBwk1dsoUeL3sW0BLIaK	2024-09-18 06:25:50	2024-09-18 06:25:50	\N	1
9	Vladislav	Khramov	Владислав Храмов 	pilot13one@rambler.ru	\N	$2y$12$WTWzddl4RqOX4DU428nAeu0DlwuhFC74DjaNQA6Fyx.QTAehL7Eti	JUPARnU5AWJLzD7MFNDPiFN5m6sIVLpNGPnrUvf1MG9K8EAxgy8sJMkTdmJS	2024-09-12 18:44:31	2024-09-12 18:44:31	\N	1
15	Georgiy	Хачатуров	GeorgiyHach	Asperin161@mail.ru	\N	$2y$12$5XMbiXBQ6Yq4je5h6xD8eOb5CRLBQbS70wnVw0yDRR0B/qvDhmc1O	Pb2d0vsockiomttYMxHDNoGBGnUxSWzjqnnIrmVn6zNxRZ31OwNU9rxOC8ge	2024-09-26 11:34:41	2024-09-26 11:34:41	\N	1
42	Anna	Berezhnaia	YouRichTravel	Bereznaaa619@gmail.com	\N	$2y$12$F.VRwC.MKiXUn0dvCmZul.fYmRRVM88kAgyusFPHVcXRbzseh9ODO	CmYFzpcAKK91VhOEnsIYJD9PwbdseFPHkHQCR7FNYA4rhoaoMNxJei6DWtL4	2024-11-29 11:51:31	2024-11-29 11:51:31	\N	1
18	Natasha	Shishkova	Fortune	Natashishkova@gmail.com	\N	$2y$12$NA92gfXICpw0NEKXdxflq.pJJ4GRHyntsvLbqek9pwNu25OOTu5XS	xKMsGljtNfu9zLZOgwOtUEeuAgwXmLIzG4lJeyN4RiSX93W7Rc46DoOH9CmN	2024-10-17 12:26:57	2024-10-17 12:26:57	\N	1
21	Olga	Mekanina	Olga117	avi.m94@mail.ru	\N	$2y$12$wPPWDNZsWj.g/LgtKGcmpuzMkDRQd9iyC4EKBz8QjyMEBESSHApYe	hUDQuwQKIoejCTjOzWMH93gacd3iACw3sLYilqUMBGr9ZvFNc6j5qWk5zzJE	2024-10-25 08:54:26	2024-10-25 08:54:26	\N	1
24	Andrey	Pagalenkin	AndreY	andreypagalenkin@yandex.ru	\N	$2y$12$Hu4VvKQUCvvq0kkf2E6EkOQZIe12sZXc51BiNRTn7GE6be9CnbSKO	4wcqfv97HyhgGrsfiL099hYcbXFjQiQLcSxd6dw81MCBBZQX5KD1HQwAnWjS	2024-11-09 13:47:28	2024-11-09 13:47:28	\N	1
53	Olga	Аникиева	Ольга	AnikievaO@gmail.com	\N	$2y$12$FNWVpcXa/4zIMPF0kkdRPuY8CixwOv3Io49Y9HD9IDg8mKGONrfxa	BLtblVgSqyGkRy3RA4cXCW0BBxKHSDlHRjbgzmuMHrctQ1yUKPo0liPEwwZs	2024-12-29 18:34:10	2024-12-30 15:51:45	\N	2
45	Tima	Timchuk	Tima	Tima8784378.12@gmail.com	\N	$2y$12$FjzmZkPblRd/4YvgaIbuleGd3LQwcbvVQRz6933M3xX8FZQcd2ES.	LyQazxYYzPNidjHy2uuMnAqPRfAkovG0kWOIOh6Utrrla30ZgOEQkI3DzreF	2024-12-09 15:52:06	2024-12-09 15:52:06	\N	1
34	Alexander	Toropov	Varnavva	toropovastavr@mail.ru	\N	$2y$12$xk7XXdoxEgwgHkKuBUfCUeb0wZVEO1aACVytUBMcqtZz5LG/ABA66	3uXol5Fmvzzg3R2w51kZiQdiaAeYgaY3iyDg52xAtCRf91P3hQb0fnhKuC5u	2024-11-10 10:52:59	2024-11-10 10:52:59	\N	1
36	Oleg	Vyshletsov	mexanuk	vyshlezov@mail.ru	\N	$2y$12$3e8Aq6vCdg8W0bnvf6hNouOsSx2qMtB.q7wvw3gAaBjX514UfYH06	Zb5gd8WGOeX4eE12gPMSHfo7saeDVSCZRocHgC1JyaKt0zEcFlMXveFi9o9Y	2024-11-17 12:08:14	2024-11-17 12:08:14	\N	1
48	Ораз	Норлан	Devlar	oraznorlan2@gmail.com	\N	$2y$12$jTIqEzf989uofOi3VMXgjOFtNd2g8FN.bcDFEVKAXlj/Xxbflc.N6	\N	2024-12-23 08:16:56	2024-12-23 08:16:56	\N	1
6	Зухра	Тошева	Zyhra	toshevazm@mail.ru	\N	$2y$12$6PeB9Q2m18eqBOoUGbbAAesXpV4RLegERyquh5mVJ3lRObAGLrnmy	xgPeqOEDrcweX62WxLXAhw8cjHPZXmnj4YBFRf4HVg9hL8EzrOansxzpwSqA	2024-09-08 13:54:10	2024-12-02 14:27:41	2024-12-02 14:27:41	1
51	Ромка	Пестовников	Ромка	Romaustug@mail.ru 	\N	$2y$12$Xeh6IKiZthfN2mk7xjiePuZs.wSfrS1EAf1XVMkhORyBPF10wxXs2	mieQBBN7QYTiebMCw83I026ruXqyb13FRpSg6lsp7eOAqGszVUri9mIHiieK	2024-12-25 16:47:10	2024-12-25 16:47:10	\N	1
59	Varvara	Beliaeva	Варвара	beliaeva.w@gmail.com	\N	$2y$12$65ol3S8KUwGFS1WgzsnnfO8wBr69/h5pMJjCgDNe0WEag3rEQw.9C	RqClvjiEdTdA11hquKu86R8IRdrkk6RkWwMtZn5HMWjBteXCDKAcMeS0Uv1F	2025-01-02 17:27:50	2025-01-02 17:40:29	\N	3
56	Sergei	Taranov	SergzArt	sergtar81@gmail.com	\N	$2y$12$oKOxScZuDTuh/VwsO5824.Vzx.WRDiBKrc12b3gjVpSPVuR8VLzRq	kvi5DmJzBseQzswx3N52JGV4EZlTWEgvTF8e8OmIOUFfryQDFlXEPnzbBBAx	2024-12-29 20:25:22	2024-12-29 20:25:22	\N	1
61	A	M	arcadio	a.mochulskij@yandex.ru	\N	$2y$12$WSNHiRMrMfoNfcEHopKMye6HMIAuzm9PrcriUVnLMNq6Gm3COI4d.	\N	2025-01-07 08:25:32	2025-01-07 08:25:32	\N	1
57	Елизавета	Головина	Елизавета	elisgryt@list.ru	\N	$2y$12$dkaf.44HITpz.Xlw4xOwoeMEiKMvsfz9rL8u5SR4Bnr.4ojW361rG	rTbwncFQdiBOtZmGDhIufjXLLwxDAYeMG2FJgrAetsbA6p0zeyeDifOG4xh6	2024-12-30 10:40:40	2024-12-30 10:40:40	\N	1
5	Алена	Архипова	Ilonka	ilonka_15@mail.ru	\N	$2y$12$f2f1ZjKRKI4DdtA51QLjROnBMsEq93JFeoCfXNc8MekBwSRIUvbfO	sON1MwgiHxdzxNfvgiNLhdLYx8GxMKicXnzB9XVAq77dB2rABj8l2VX7rTdt	2024-09-01 15:25:09	2024-09-01 15:25:09	\N	1
4504	Alexander 	Ugryumov 	Sven1980	sanya.ugrum80@mail.ru	\N	$2b$07$4fb5IcCb0eWYefXLEUb4ROQp4vnpJJeqyvjnUTXjWzxdAqUf50uyy	sBGEe24aMcbL0jhxXpX6uBmCF6MWHBLYhzwwJYl19gj31kHel5b4jHIATewU	2024-08-20 10:00:00	\N	\N	1
4506	John	Doe	testuser2	test@mail.com	\N	$2b$07$gCM3XuPB1YYFvkpOoagNUupBC.RzxzTIvsaKMUHwO9TrJH5p6Lp7q	\N	2024-08-20 10:00:00	2024-09-17 09:33:00	2024-09-17 09:33:00	1
4508	Светлана	Волкова	Svetik3	Rada-1706@mail.ru	\N	$2b$07$UhaHUB0wHu.nKBH7PLGRyOH439XrNKv6kLhrwOV82JCkMP2Z6hRp6	NMGSKpn9FZ8nOttAS323QqqW2Q1mtqTHD0ut2NpikihilGZzgWVvGeI7q46n	2024-08-20 10:00:00	\N	\N	1
4489	King	Valis	user1	timecps@yandex.ru	\N	$2b$07$OjJoD90aXJ1imha3rE3iYuPXAcui6PzMQYtkQJ.5vZM4TefO9fHGW	\N	2024-08-20 10:00:00	2024-09-17 09:33:29	2024-09-17 09:33:29	1
4488	dsfsdf	sdfsdfds	esdfsdfsdf	sdfsadfas@intesknf.com	\N	$2b$07$3qs0WWyTUlbNkLBXIdhA6O15mkiJLb.0XpedERpb8LBoghibeOaZe	\N	2024-08-20 10:00:00	2024-09-17 09:33:33	2024-09-17 09:33:33	1
4496	John	Doe	mapupa	exm@jsds.sjdf	\N	$2b$07$BXE43kkCv9gpQ6W3uqYab.Hz/gBG31H/O3bnaadSEJe08BzT3ecpC	\N	2024-08-20 10:00:00	2024-09-17 09:27:28	2024-09-17 09:27:28	1
4482	Abuka	Dabuka	Shalushai	asdfds@dsfkhjm.sdjf	\N	$2b$07$uJuzd7VTFikwkwht17nlnONQ.d.qjC2Qj4wA7BkELfukROdFuIJXq	\N	2024-08-20 10:00:00	2024-09-17 09:28:24	2024-09-17 09:28:24	1
4469	Dmitriy	Marakulin	Gabber	marakulin_05@mail.ru	\N	$2b$07$F4am.VaF0dHANHR0utUWPuLwLmkRaNE6GO5pbgOYg6q4JdaPDOoYa	\N	2024-08-20 10:00:00	2024-09-17 09:29:23	2024-09-17 09:29:23	1
4465	Алена	Бондарь	Alena	alena.bondar.2012@gmail.com	\N	$2b$07$qn.UrSJDuuwmhkG0xyUXEegXlJXDSIz40Y..O0Ct6NOvFr/Daz0/G	\N	2024-08-20 10:00:00	2024-09-17 09:29:45	2024-09-17 09:29:45	1
4468	Anton	Feshenko	Антон	Pro-steroid@mail.ru	\N	$2b$07$HrWfnqDI9FqveAqJ3/eS2ueviw/SJGDwTS6MX7PH.gF1BMkuNUQUO	\N	2024-08-20 10:00:00	2024-09-17 09:33:50	2024-09-17 09:33:50	1
4453	Vlad	Ponomarev	VladPanam	vlad.ponomarevsw@gmail.com	\N	$2b$07$yVb8/qYjhQV0j85crz6wzeueIg3n/GVsa1/miE.YFG3eiOoCcnYSG	\N	2024-08-20 10:00:00	2024-09-17 09:30:06	2024-09-17 09:30:06	1
4452	Dmitrii	Bulatov 	Bullet	armbrothers28@gmail.com	\N	$2b$07$4bYZyKiPg313lXQC5MZfP.FXFE9neBMPXhX/cjFXGQ04yydhZ4Zgy	\N	2024-08-20 10:00:00	2024-09-17 09:30:08	2024-09-17 09:30:08	1
4451	samuel	micado	samuel1987	samuel1987@yahoo.com	\N	$2b$07$20JtLAx1mWi7IOjvJzirMO7juirP1wj5ap7FCrX0NfeEXEq4/8lnS	\N	2024-08-20 10:00:00	2024-09-17 09:30:16	2024-09-17 09:30:16	1
4450	Евгений	Мишин	evenspb	evenspb@gmail.com	\N	$2b$07$VgsPMKvenoCB5f/duqHrBeKPD6WJfWYWjk5TqH3hpMPrxIZ./41m.	\N	2024-08-20 10:00:00	2024-09-17 09:30:20	2024-09-17 09:30:20	1
4467	Елена	Виталий 	Елена	elena.mash@bk.ru	\N	$2b$07$q3pDaSHcNnFN7OJHdRDKK.vUd.qLRrX5ibQxBvkTN8WMVBM41SPQK	\N	2024-08-20 10:00:00	2024-09-17 09:33:58	2024-09-17 09:33:58	1
4444	Олександр	Тихоненко	Котик	tixonenko.sawa.1975@gmail.com	\N	$2b$07$kQOkEzUurLaz.xZIptt4i..oRV6PWHby060kw2xLi.yfMkBqoDMOW	\N	2024-08-20 10:00:00	2024-09-17 09:30:40	2024-09-17 09:30:40	1
7	Георгий 	Хачатуров	ГеоХач 	Dgon258@mail.ru	\N	$2y$12$LW0uLfplhI9Pz/1I77E.r.nY12AhIKwLc5y.nXgbcu6zuHcM/3cJe	\N	2024-09-08 13:55:39	2024-09-26 11:32:20	2024-09-26 11:32:20	1
2	123	123	123	123@123.12	\N	$2y$12$E46MEbcTSA7wpMDKbEhgpeQk43qV1eJU5PnCQXnYT9u6eOafxTTWS	\N	2024-08-25 14:22:51	2024-09-17 09:31:04	2024-09-17 09:31:04	1
4	John	Doe	abs	example@mail.ru	\N	$2y$12$pJoAr3Ws1fZhKo6tol8C8egB5e1qcslFCDdYh80dIG5seMAth010e	\N	2024-08-26 16:18:35	2024-09-17 09:31:17	2024-09-17 09:31:17	1
13	Юрий	Морозов	Yuriy	6svq4@rustyload.com	\N	$2y$12$ofqiMtot/DX4shdrJgS7W.zWQz.HNSZuuWy3MZ02/DfWurGtesnyy	\N	2024-09-23 10:03:54	2024-09-23 10:03:54	\N	1
4507	Виктория	Дейнека	Виктория	v.port@bk.ru	\N	$2b$07$c5rWBd0VGyCMx2noAq2lKu9uKhr09ktnGlDKT6pKp1uJFKQZZ3Rpe	\N	2024-08-20 10:00:00	\N	\N	1
4513	Михаил	Тарасов	MIKHAIL-61	tarasov-61@rambler.ru	\N	$2b$07$zkbaREtMh3koMkGNKX2AU.WbCnxpQs06UvNtVqLNvKwp.Ag8FnHTW	TRUjWxUQhPsOfEcULkAeR7NKdLr6G2JgiWW5m9PZub8nNCOMQNeNyQMVKYHo	2024-08-20 10:00:00	2024-10-27 10:33:05	\N	3
19	Gertz	Шишков	Илья	gertz78@gmail.com	\N	$2y$12$bYVIqtxmQ.5NhYJpZQrkiO2Tcz8n8wxjmSTZY7UQt6jzHYudIm3aK	PXlVVpUy3kZf3KVB40YEV0uY329Hb9mA55oI4QibOP5OPgS53aGfJEWgkXq2	2024-10-18 10:31:25	2024-10-18 10:31:25	\N	1
37	Victoriya	Popova	Victoriya	Kini2706@yandex.ru 	\N	$2y$12$0m2KGsjYbZuoEZ1SyqOniODB91/WoN8MBJkMnEJlcPDK3nEtiqdNO	TqfdyBbiNC9wtiphSEZzqACG3k9rdl7Qg3exgfKZHC2cWhC7s3j5vASIHdbB	2024-11-20 16:54:05	2024-11-20 16:54:05	\N	1
43	Наталья	Минтагирова 	tusya1985	mintagirovanatalya@icloud.com	\N	$2y$12$JZNAnm7kr/A7rpeyviGc9.V5XWWf4/xt2x4c5MwITzyW3V1onrBPS	0unQMvynlsWO7Asnggv7xrBM6wbVn4YSAF58sPo7YfS1Lthv2KaF6sTYMuhL	2024-11-30 14:04:48	2024-11-30 14:04:48	\N	1
16	Семён 	Сибирко	Glouze	smartfon228007@gmail.com	\N	$2a$12$Nvp3rqYCdIWpe87y9XVKWO7yx0u7orJJqQEc1gtm/wTgXmvioLl9u	JPOLKulOQuZsarWGY78yUYs2q3VRBclEhZ7Eb9TMj59NQG9xAa9RtlWbOell	2024-09-26 13:08:53	2024-09-26 13:08:53	\N	1
4459	Evgeniy	Malinovski	finn	finn7777@yandex.ru	\N	$2b$07$0v3KWad.p3eRyEZAd4rw5OPGafa/QUpME6x0WTLhVCohjTeLfTU7y	iSk7GaLflgGKHhjkEBRkh1uZJzf5Dgid2vq3Dddj1iBtSMpaocX9DZ253r3T	2024-08-20 10:00:00	\N	\N	1
3	Ахмет	Ахмет	Ахмет	axmetov-da@yandex.ru	\N	$2y$12$wnGd3Fnb3oEJ/sq34Ppiw.fdNGhcT4PFn8lgtdsAaQggoX4r/TXuW	oxK11rlt94U9fv8S9UD55fEa7uBdLN3HQ2VcQ8F8mKVPHsKD3XiRC4hVPmfK	2024-08-26 06:12:13	2024-08-26 06:12:13	\N	1
40	Katerina	Kotova	Katerina	zvezda03031988@mail.ru	\N	$2y$12$HnfeAxdrJT8KLOP3qfX2dODqox6kj0bF6iTSgShAPPNzXak8Fw2kW	\N	2024-11-28 15:16:22	2024-11-28 15:16:22	\N	1
4503	Sveta	Volkova	Svetik1	Rada-706@mail.ru	\N	$2b$07$ozonihXZjYqqbybhG2Vb2e711GCAuIQoOZM2YMtIFPbiHRej4P8r6	iw2BWNp01WNwf2FGdQjOdXAm8sFxpw2hJ79YvedE5bsMYgsLpPmO7UCodVI6	2024-08-20 10:00:00	\N	\N	1
4498	Ivan	Lobzin	timeivan	infoprizmspace@gmail.com	\N	$2b$07$wtF88dwO/OlQmjzO6mrTWuJo5h.dl/o.yEFXih/4ww65rW28nFr.y	hSwAeYMMPN9KTK4VZnnI41Lb67KEOPhxbZt8DCSZAUXkyizJ7gfJ7F94RFl8	2024-08-20 10:00:00	\N	\N	1
46	Иван	Синицкий 	Иван Сергеевич 	sinickii9@gmail.com	\N	$2y$12$GtFke2wPtQBSBNGYZEUJCux2BgBkI9KlK2oYp5Crf3w3EZypJcbUm	xHDqX9W4QwPGVyyyTOqL6xpf7BgcAKlLBjayGddWopoF5Ph0La5Ex2jVhPPw	2024-12-10 10:09:49	2024-12-10 10:09:49	\N	1
4505	Светлана	Волкова	Svetik2	Rada-7061@mail.ru	\N	$2b$07$OjA0swGWVP8SQs1MJMgOA.O5LLZWr8mhpvI9OWltOqiWJ1QNazbe.	O7ZoFxeIPlEYtRcgJDH2HVe7EVvyodLwgIo2WQUg6Ta46C2K4rYi0KrWuqHp	2024-08-20 10:00:00	2024-11-21 12:34:49	\N	3
52	Maria	Kostromitina	Mahins	Mahins@bk.ru	\N	$2y$12$oft1pG/9QHcUyy.kpsFRiuiBLpJvGDSW4W9tY3Bida4fzOXCxPb9W	NWgoKBUbFSqD7dXSrf3nJ8HuXeMhzF9eKxuD2mp9Baalhx5eDjxjhH4eRhnM	2024-12-29 12:42:05	2024-12-29 12:42:05	\N	1
1	John	Doe	testuser	testuser@gmail.com	\N	$2y$12$W0gKlpYipk1TmjwnaRXeI.Qqm494UpyAp3AmYz5Hh77POE6dRTWvm	1PMLwXxKz3tosaWd8zL3Sc9BIPNREUd9f1qhFYxVxRWWfFQKi8yVCIQwYItt	2024-08-25 14:19:08	2024-09-17 09:31:03	\N	1
54	Tatyana	Кулакова	Татьяна	Grin-Anik@yandex.ru	\N	$2y$12$ePShxUqKRXLOJhe38hveBeREFzQ25wMTVXs0zDOl/RODVz.XiKDyG	QCoLbyICp85o1GzgJ1dGc6V5nnVMTSzV1HCzJ7PpATaqGkLJgPXfhkP3lKDr	2024-12-29 19:00:16	2024-12-29 19:00:16	\N	1
8	Евгениц	Мишин	Evenspb2	oknometriy@bk.ru	\N	$2y$12$QtmNkNYqjlFseV..9hHljerZot8X8LDm9piKtZAitNrAH/OVecy0K	YNHuc45H9qhW2ULysu3Bp4DaCc3mY2IcOvfNu1RlvFS8PUdQ5kZaDWojRzry	2024-09-08 17:20:09	2024-09-08 17:20:09	\N	1
4494	Marina	Ioyleva	Marina2	1marioy45559@gmail.com	\N	$2b$07$DY7AjIw3zWF79Rv.3edNJugZkMJjTk0w.Bo9jSH59GCECHsZa92DW	SQQtF2LbsoAAuUHVsGManzY5EcKkeY3ArrnDn4rEd5WafCE1VVA5WZbOySwR	2024-08-20 10:00:00	\N	\N	1
4495	Marina	Ioyleva	Marina3	marioy455592@gmail.com	\N	$2b$07$MLbsRR7ImuuTKVP1e.PYZu1eEytnCr8khPTw5cWwt9LtED.1/ymdK	ojt5sGukZbB8af53wvc6YWY4VFUi5PAHeApeXxdxrcarSxJ9W29H0BVIlHkZ	2024-08-20 10:00:00	\N	\N	1
4500	Borisovlox	Borisovlox	borisovlox	pavlik.bart@mail.ru	\N	$2b$07$baG3DMrRu2LU/FW40xZM9O./TS/4zlVx0xLmZY1nRbGp4NAMYQBUG	eo242hVzRAz0OqBjfpM1f7xS4FV3ZoQKiRyOyg6cjVOquZymZlUXD7hvlokC	2024-08-20 10:00:00	\N	\N	1
4511	Ss	Ss	аааа	Ssssa@gmail.com	\N	$2b$07$H/6Vmb0..5ia185X84FYLOCJRvR8cObw8r6hge6nC0T77ZiB02arq	\N	2024-08-20 10:00:00	2024-09-17 09:24:06	2024-09-17 09:24:06	1
4470	Михаэль 	Шаломонов 	Myihael999	Mschalomonov5@yandex.ru	\N	$2b$07$9BWccgXymqKZvGOAAXMmX.OwVUH8HTX7yqZv1fEgFHWECv7.QA77W	tGqiozk9K2ssdlP6h034TCC4iJyt7zY0O5dWevZeBHGcLm9kBpnPddbsCU9A	2024-08-20 10:00:00	2024-10-27 10:33:50	\N	5
4492	John	Doe	user21	asfkkjl@ajf.ahsdfkh	\N	$2b$07$O72FPMjP0tn35aFfUBLE1uUvGKmcP10XUpIfPFgEAMdTIhj5b4XAO	\N	2024-08-20 10:00:00	2024-09-17 09:24:26	2024-09-17 09:24:26	1
4491	John	Doe	user20	example@gmail.com	\N	$2b$07$ImE7orVRI9NtRVOnG.ONeunVE.YHVCgncqAO.J.ffJZH.n9weOWpO	\N	2024-08-20 10:00:00	2024-09-17 09:24:29	2024-09-17 09:24:29	1
4484	Hog	Raider	Hograider	Hograider2021@gmail.com	\N	$2b$07$0l7XJMibH9I7SzspnmOzvuUab4msERFxgWtTWcGNrgNJgfj9i2/Hq	\N	2024-08-20 10:00:00	2024-09-17 09:27:59	2024-09-17 09:27:59	1
4483	Ivan	Lobzin	spasadmin	g89951820385@gmail.com	\N	$2b$07$OaFRNkfLxJB1SfbE9qhCQOmao2A4lyOgfAdPgIGmcN10IrP1ueH6S	\N	2024-08-20 10:00:00	2024-09-17 09:28:10	2024-09-17 09:28:10	1
4477	Marat	Khabibullin	firtes	art94238@gmail.com	\N	$2b$07$0WVKCTleIc8idiByh8Pif.92R2m8Ngng/G3xJfP8b6NUi2Xo2Umpy	\N	2024-08-20 10:00:00	2024-09-17 09:28:34	2024-09-17 09:28:34	1
4476	ddd	sss	ввв	marakulin.820329@gmail.com	\N	$2b$07$PD1l3nutNW.ogBpX6tOAwO54HuFeVLSe3M67GLhq8Kg4PtRd3n.Aq	\N	2024-08-20 10:00:00	2024-09-17 09:28:44	2024-09-17 09:28:44	1
4475	Richy 	Rich	Richy	kirillrich@gmail.com	\N	$2b$07$PhI.A.jDQv0nRrG0SYcCRuCIURjcPVOXJ1pPnS1dAJGCmgNo8an/K	\N	2024-08-20 10:00:00	2024-09-17 09:28:45	2024-09-17 09:28:45	1
4474	Александр	Терехов	tod15	nid369392@yandex.ru	\N	$2b$07$a25hd0OVJetaB0.UiUUDSOf/FCc1ytyJ6RJ5xAzgOSop/MtBZBmQy	\N	2024-08-20 10:00:00	2024-09-17 09:28:53	2024-09-17 09:28:53	1
4473	Александр	Красилов	krasil	ivansulyagin@yandex.ru	\N	$2b$07$7UeqnEYDrcQdzv8TkvkxV.aYmO.esR.Si9GafJd/GQKP0eUkDhAWW	\N	2024-08-20 10:00:00	2024-09-17 09:29:04	2024-09-17 09:29:04	1
4472	Ruslan	Tleulessov	gessar	gessar2010@gmail.com	\N	$2b$07$mm1fNSdDGOkdhvbVjmCMKuHICXfuFrhGccrCPmaRDtQPQKlU5nbj.	\N	2024-08-20 10:00:00	2024-09-17 09:29:20	2024-09-17 09:29:20	1
4462	Anna	Polis	Annapolis	Hdhdhdhd@udh.ru	\N	$2a$07$6u9NJZM2igcnN4maeRvkyOTCO.1ODmZDEIwhfP6qEk96CUAD8hnKK	\N	2024-08-20 10:00:00	2024-09-17 09:29:49	2024-09-17 09:29:49	1
4461	Kim	eunhee	Kim	dimabullet2828@mail.ru	\N	$2b$07$yO643KSbm0zq5rwHd5q9yO/6D.7bN25Yr.fs5Z8YZFgqYGVzj4QPy	\N	2024-08-20 10:00:00	2024-09-17 09:29:53	2024-09-17 09:29:53	1
4460	Andrey	Sh	AndreySh	Qwer@rt.fg	\N	$2b$07$Q75efr0ZRs3nTf40czE4RuiMHRFehuOqjDRNhsr7zORkoeFWejX2.	\N	2024-08-20 10:00:00	2024-09-17 09:29:55	2024-09-17 09:29:55	1
4458	Надежда	Кнопка	Надежда	n.homiack2014@yandex.ru	\N	$2b$07$2siZnSnY6ng2hQcaPtadEehTBFG/QbdXjVZTT3B/Ee6qWyhqUd7HO	\N	2024-08-20 10:00:00	2024-09-17 09:30:01	2024-09-17 09:30:01	1
4457	Kazakov	Pavel 	Kazakbro	Kazakov1766_3@gmail.com	\N	$2b$07$Q/NTZBzUilj2r9QWtYBD0.H5G5SGng8eJ5mqRDLv10H2SXbQPITsC	\N	2024-08-20 10:00:00	2024-09-17 09:30:04	2024-09-17 09:30:04	1
4456	Artur	Kazakov	Artur116	liza.petrova96@mail.ru	\N	$2b$07$twd6IcGDQmvrwpN7KBl3CuM2yTKJJAt.xrGKNQ4gMGfzxlAZbBBcG	\N	2024-08-20 10:00:00	2024-09-17 09:30:05	2024-09-17 09:30:05	1
4449	Crypto	Gold	Crypto	bujacserghei4@gmail.com	\N	$2b$07$HFtzAKvbMObWda08jH/DpuYmCSZtpwOcdljm.G52Jsw2cttZWsQPG	\N	2024-08-20 10:00:00	2024-09-17 09:30:24	2024-09-17 09:30:24	1
4448	John	Doe	Fidel	david@gmail.com	\N	$2b$07$Os0BvCRyIOA1QI2N5qK3z.HO6qh69uP/mXlLUSzrxd3wK0van..Gu	\N	2024-08-20 10:00:00	2024-09-17 09:30:26	2024-09-17 09:30:26	1
4447	Евгений	Полушкин	Chipik	chipik1990@inbox.ru	\N	$2b$07$WhraM/OtAsIVlF8.QInguOda/iCGpCOuR5Z.rQ0i0bGgb.yrSfOa.	\N	2024-08-20 10:00:00	2024-09-17 09:30:28	2024-09-17 09:30:28	1
4445	Селезнев	Михаил	Михаил	mikhail1987.10@gmail.com	\N	$2b$07$rfBrrfNGkXiAgAVQ9g15ku0rUHrpplsPNmqJCQhoywJSeHp73LrcS	\N	2024-08-20 10:00:00	2024-09-17 09:30:37	2024-09-17 09:30:37	1
4443	Igor	Lonshakov	Liv1969	igorek012@gmail.com	\N	$2b$07$FvUj/.zY28NNNvZu.83QieeznuPYLIlaatCLM/97cuRKu7sj0d5US	\N	2024-08-20 10:00:00	2024-09-17 09:30:41	2024-09-17 09:30:41	1
4442	Gangbang	Dyson	Popcornyou	Homegate3445@gmail.com	\N	$2b$07$hRLalZMszXAaoTfnSXW7VeQghQfHEHKXkgzwvexKfXsQbe/3.VooG	\N	2024-08-20 10:00:00	2024-09-17 09:30:43	2024-09-17 09:30:43	1
4441	Виктория	Семенчук	Рыжая	sSmenchuk_vichka@mail.ru	\N	$2b$07$lFFP1RPDzMIW4BzpC/FpkO2t03wW1vBDdwlVyvZq02H9GHpzBDuZG	\N	2024-08-20 10:00:00	2024-09-17 09:30:46	2024-09-17 09:30:46	1
4440	Dmitriy	Livanov	dileaveme	dimitriy.livanov@mail.ru	\N	$2b$07$rzRIyKEeA8xXmXR/SNBCl.FUJQVntJAZqfjBVGKaYhr.YOoScFjcK	\N	2024-08-20 10:00:00	2024-09-17 09:30:53	2024-09-17 09:30:53	1
4438	Миха	Ковален	zoloto	bykovih4@gmail.com	\N	$2b$07$P2lD2BHWPLSvRLixRjooyumukbFiao5RGJjYqtUUS2BbyZj2Fgfoa	\N	2024-08-20 10:00:00	2024-09-17 09:32:20	2024-09-17 09:32:20	1
4439	Aндрей	Кочергин	Валентиныч	a35660761@gmail.com	\N	$2b$07$hHZpcDVIir1bQzfYM8pmKeelOI0gknZOIyB1pfXI1H8it.roUd1Wy	\N	2024-08-20 10:00:00	2024-09-17 09:32:27	2024-09-17 09:32:27	1
4446	Селезнев	Михаил	Mikhail1987	mikhail1987_10@mail.ru	\N	$2b$07$QSkxDeMrQI27zmjDuvq3gO1plQqfJYgPkSBjaOaP3lIt8MD6SMAVi	\N	2024-08-20 10:00:00	2024-09-17 09:32:34	2024-09-17 09:32:34	1
14	Marina	Klimko	moreland	mirceley@gmail.com	\N	$2y$12$qblPpWAFHqHT4UZ0EnBWqu2u3saFzcKsjTmGPXzcygXU2I4ulOYYu	cMFqy0b8oLW2VH718VFGA4gFVAVR5gZD1PYMWNVE1SO9lzZegBR65Wv7Gesx	2024-09-25 13:10:12	2024-09-25 13:10:12	\N	1
4466	Rustem	Ahun	RustemAhun	Hdjeh@jf.fh	\N	$2b$07$bytgmwrxWAH4gdOXqin74uklhmmL.9t/.5jTI27Y1fiZ5CAnrG5uy	krtJMIQnqP2vtgyC5uu19FQwHltgQHYHgpXpblAt0m5RatTaGYsnnqPbfw4E	2024-08-20 10:00:00	\N	\N	1
4490	Марина	Иойлева	Marina	marioy45559@gmail.com	\N	$2b$07$JdB.BthI7OsouZGeaAOdnuGIFuFOL2Q/olC2He4ERcK.pvCZ3aOMy	uNbOmrgtqgs5OXiXjrkHCRuy591zwIZyj1oDE5ueUfXd2rgvt3gwpUsPcF1v	2024-08-20 10:00:00	2024-10-27 10:34:30	\N	3
4493	Арина	Белова	arina	arina06081984@gmail.com	\N	$2b$07$0vCGekQOAI2Zpx/mM8Um9Oz3l0sW0R8ULh8GJ0ztDagozHhXBfxj6	W2lCl3mO9YSDMG66Kkd53kla6YQ9whj18CrxbGTIdojBK8ajBTbNWqbTTwkA	2024-08-20 10:00:00	\N	\N	1
22	Евгения	Шумарова	EvgMih	tematar123@gmail.com	\N	$2y$12$OanD5YeRONk9l90LydAZUuLXWNDHrL5i5KhO2PZwo1dXvTmeBinJ.	uuPhRyLtgqtTYfxkkqcqHxbSU6j1TBfMeRo6wQQom7rvClXO8Dj7GK1DjxEH	2024-11-05 17:37:48	2024-11-05 17:37:48	\N	1
4497	Svetlana	Volkova	Svetik	rada-706@mail.ru	\N	$2b$07$e5/6Bnp7j92fRTl4GPN5pedRaqkn/FIlWn3hqnynynwoB9NqGf05.	QhpxE2eIUizGAAxCpzQEPSbmMotheKapxFTmzqaKpx4N00wXFsPBPyEa1ba8	2024-08-20 10:00:00	2024-10-27 10:34:13	\N	3
4485	Alexander	Vinter	Alexander	kochevs19033@gmail.com	\N	$2b$07$TzSKEl05iOBWDKZfKMeUpOnkozlVnm9AAvywZoKzuovcNuPgdn96S	qS4memr5zvtWNKycwW2UsoGPi6uApUth5zNXuamlYjMndkR04PvKFd0SIpfl	2024-08-20 10:00:00	\N	\N	1
17	Натали	Шарыпова	Натали	SharypovaNatasha29ru@yandex.ru	\N	$2y$12$ulcJy.Lg9TTKl2o8/gOJb./y9GufZ1x6KWugDppENmHd96w176pMq	HoOk32J8FSKok5bg1VyQOtzdq2KlGD2K5kJusDOec5WEUb6mbZFRXndNfHV1	2024-10-13 13:10:10	2024-10-13 13:10:10	\N	1
20	Natalia	Sokolova	Natali	sna2503@icloud.com	\N	$2y$12$.g7qdYD8.IQ74O5I/fyfPuLFNmt4n5uzghepBQwHzfeZOhzxy3c/e	4yn8kyQfH4wN2orATe5uzrS82e2USz1ODyBeF7bN8fU2FEmvDT4TXX4wUDim	2024-10-23 09:25:48	2024-11-23 12:16:56	\N	2
30	Григорий 	Бабарайка 	gb@mixrest.ru	gb@mixrest.ru	\N	$2y$12$dtsg.trSbv/ST/0x7aUA.e2ng1s7hLyW9GB2wt3ELSd9TLcf4I9ca	Ln3Tvo3NMcEF8UxN32Y9lwDTGrtVjDCTg0dXQyVRIjf6FgVwFTS3jNDvOfIE	2024-11-10 08:56:39	2024-11-10 08:56:39	\N	1
58	Анастасия	Волович	Nastayslash 	nastayslash@gmail.com	\N	$2y$12$7Ty5goAjd9zWhFhKtR98SeS2Qe6mDHsexecGTF1NcXljmN6UknaAm	7eGYVQ8fm8PDXcpWxmpAtrOD0VM1r5dcoePocoH1JW41wY0xskWWjEhy1Kcd	2025-01-01 14:12:16	2025-01-01 14:12:16	\N	1
4464	Alexey	Lisin	LisRS29	LisRS29@tt.dd	\N	$2b$07$rTmPHD7DRPo4cxt1j5AhfOV8Y/972vKbokmI857b1.JXoTo5xsg82	Zq8N4Qs89u3LsyQJla0jlm33aikPhRCJXqzNjrdZLfByeF1Qs3zcxzd4OVUv	2024-08-20 10:00:00	2024-11-17 17:53:04	\N	3
55	Владимир	Аникиев	vladimiranikiev	vladim.anikiev@gmail.com	\N	$2y$12$0x9ftlzzmb.owz3igkB3Z.PJ6GbnkAX8BfoRjvIGvSzWHIVgbqw8W	QyLoDrBRdrnBKPB1MjedS3Yp8DQtKy4sEYbsf9wDS6IL38ZdHgNuaaIH0Gsc	2024-12-29 19:15:25	2024-12-29 19:15:25	\N	1
41	Dmitriy	Dvinskoy	dvinskoy 	dmitrijbelyj555@gmail.com	\N	$2y$12$meYYdn097Xu5IVkMkLBKie958auHCOHsSIVGXl6dFJ70R.HsQH7Ya	\N	2024-11-28 15:50:56	2024-11-28 15:50:56	\N	1
47	Olga	Printseva	Olga	printseva@internet.ru	\N	$2y$12$j4mTW.Pa9p.QBt0gKEduIerjT/liCIWgIzFpre.qm3aIai.oJNBGO	\N	2024-12-18 10:52:50	2024-12-18 10:52:50	\N	1
10	Mikhail	Duvanov	Danlym	duvanov.m@gmail.com	\N	$2a$12$UqXlLAfVsnoYcSFN8WKGMO3TnOYLuCigKtOibjw/wG8RaoQ8xDq7y	Pr3H1QFgeWFZJ4n2tXMllNnRN8mbgAMXrmdQIHAWJioKSgqI3k8z7O7WxYWe	2024-09-16 12:26:02	2024-09-16 12:26:02	\N	1
38	Svetlana	Tregub	Svetlana	svtregub@yandex.ru	\N	$2y$12$fUXPUf9O4XJ40IJnQfXT6erPiGLo8wr6q6aKcrPFdmjLMhdAt2/0e	ggIIVyucjUYdoWqJo3ovAx3jOrOwRu0VfqgLgqmBlLRe869YwPLtgzdHj2DY	2024-11-21 10:58:55	2024-12-30 15:50:19	\N	3
35	Ilya	Toropov	Varnavvaya1	ilya.toropov.2024@gmail.com 	\N	$2y$12$jjOOGtFjyH6S74.VTIkO0.iJgWRlyDNTCPoUpA5hhYbq3u.4MmDqW	2EPu0SAcsgfkdDf3dce8SZ2o3CXWqNIClEiuhMeySOe9jEkwOTwDYMXmu8He	2024-11-11 19:53:09	2024-11-11 19:53:09	\N	1
23	Дмитрий 	Шумаров 	ShumDi	psgroupp@yandex.ru 	\N	$2y$12$b/fNdHFtx6E5SqckajAlzO1LacXVkIYMmeUu.4NQ17aEgqNuwQX5.	H2G5RU1zHvHcRqLo6I7Coec47EtdFm656HHeX21sLwO5aikh16yMPOptt29B	2024-11-05 18:14:32	2024-11-05 18:14:32	\N	1
12	Doqch	Докучаев	Андрей	Mummadok@gmail.com	\N	$2y$12$5RUoqqmg47jcIHc9fVnflOvBmSwIkQ7/vEpfiHv56DOVxXhluUIi.	QjhDYoKxrROdREjjE8k4mmv4Qt6PDTWUvZuLJlnwR6Vol0sC6MuxdMnQNEXY	2024-09-18 11:12:24	2024-09-18 11:12:24	\N	1
44	Ирина	Бреславец	i_ra_bres	irabreslavec69@gmail.com	\N	$2y$12$ySYHzWKnEmg.5HvHT8hL7uBAK087EcEy.bR6CfCxlrpUNRjHnCdzW	BUFMFaGSp6Eb4Qo0zP59abmjqeTSd19NMRVOKUjmeYwqg4uhy12PUn01sQUc	2024-11-30 21:45:21	2024-11-30 21:45:21	\N	1
4501	Alexandr	Kondratov	Kondratov	kondratovsasha@gmail.com	\N	$2a$07$B6CAz2SAbB0dV0CX.0C6bOAXyoLXQNTUH9PM1OG/OexbhGVqK/xsy	ZQ78YA3Cm7NM5ER1SSSpUNM9GDbHACDRXiohxrA69bDMTmUi9ffVrIKi6lK0	2024-08-20 10:00:00	2024-12-09 16:04:27	\N	3
50	Maria	Kuznecova	Мария	masha.kun.2003@gmail.com	\N	$2y$12$8fhNveQGSur1J2f3Mxa5fehwbx8hwx5zlXGYAfSQzL6A6J8Qurs9i	EmcrfSVZaEkL1WUjFCSSQMGErbDpymtUlo5rmoCxGEaZlFk82gpiJXpTzwKp	2024-12-25 14:39:10	2024-12-25 14:39:10	\N	1
60	John	John	John	black082765@gmail.com	\N	$2y$12$MoLlQCgatv/plqJQd94xJew2QeYYrBKw7nSGYub3ODiDq5oNK021S	\N	2025-01-06 14:21:03	2025-01-06 14:21:03	\N	1
49	R	R	NikolayR	NikolayR@NikolayR.NikolayR	\N	$2y$12$E9Vz054.fzhWV3kXXSgcuuP7vHEezgRz80Hj7kHCdh7/UgFItc2/2	Sq1TP5dbXMCJBrLTFXe4kMxipGTHSFmmKVyRgus5ucVac6008a041sdqny2P	2024-12-23 15:42:22	2024-12-29 13:57:11	\N	5
4471	Lubov	Karamisheva	Kalunik_23	Lubyshka-lubov@mail.ru	\N	$2a$12$m7ngoaFwZvAVNV0W96KZ3OT0eOKaRauOVtSlIP6ZCZHWYioWv2dIG	QKjtMgMzRnWzFz8JPFLbu9y0YEZoe6J5RQBGuLWOUbm250ctrPfhNj7QsLft	2024-08-20 10:00:00	\N	\N	1
4510	Ksenia	Ekhlakova	Ksenya	1v.v.ekhlakov_22@mail.ru	\N	$2b$07$ucbUngZZBuvG2BrBfoz55u2FT3Pq/Vis9aLoYeUCo9Aq/lImnO96G	zZS5MDv6Aujdy3zCvSkvto4sROn3hHs8LNIq7zYk6e7s1X54Xre0LBA6VvdH	2024-08-20 10:00:00	\N	\N	1
4512	TATYANA	KARAMYSHEVA	TATYANA812	1lubyshka-lubov@mail.ru	\N	$2b$07$zEG5cMggoWsA3Xf13fGMhO6bN4.aY1ascMhxFIbGtxL3c4NSSJIQ6	dccZPEWp3fmzvwQTbf7BLhrOmdMT4FbPACCEL0ks9HOVSJyYwWlZheHlM4H0	2024-08-20 10:00:00	\N	\N	1
4509	Vyacheslav	Ekhlakov	VyacheslaF	v.v.ekhlakov_22@mail.ru	\N	$2b$07$JatYwlI7Lxft9Y1hR.uIYuYsSWiFrt63YTLfUmakoz4Uveip8b0Tm	lKjkQz0lWXiYfjAhfMd3f3DDzBo8igE8mqsgUs9WKjAfRGskcXqagH2TzbXo	2024-08-20 10:00:00	\N	\N	1
\.


--
-- Data for Name: withdraws; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.withdraws (id, uuid, commission, wallet_address, currency, trx_hash, created_at, updated_at) FROM stdin;
1	WP-Lxuy7LXZdC	3.84000000	2200300516861067	usdt_trc_20	\N	2024-09-01 12:28:29	2024-09-01 12:28:29
2	WP-GdOuPG7kqC	5.74000000	2200246013476323	usdt_trc_20	\N	2024-09-01 17:39:46	2024-09-01 17:39:46
3	WP-BWURWcz6qT	3.88000000	2200246013476323	usdt_trc_20	\N	2024-09-01 17:41:31	2024-09-01 17:41:31
4	WP-opUoi7Bem6	4.50000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-09-08 15:06:18	2024-09-08 15:06:18
5	WP-A6IZuYZJ5D	2.40000000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-09-08 15:19:04	2024-09-08 15:19:04
6	WP-eXMr0XsuIa	10.22000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-09-08 16:03:17	2024-09-08 16:03:17
7	WP-agz8IdDqQR	8.46000000	2200240283877799	usdt_trc_20	\N	2024-09-15 11:14:17	2024-09-15 11:14:17
8	WP-y35YwEpsn5	5.12000000	2200240283877799	usdt_trc_20	\N	2024-09-15 11:31:49	2024-09-15 11:31:49
9	WP-H16R89IKSl	5.52000000	2200240283877799	usdt_trc_20	\N	2024-09-15 11:49:42	2024-09-15 11:49:42
10	WP-EZZc5hDxWw	5.50000000	2200240283877799	usdt_trc_20	\N	2024-09-15 11:54:01	2024-09-15 11:54:01
11	WP-EXE9skcnrq	9.98000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-09-15 15:42:45	2024-09-15 15:42:45
12	WP-3EcJeb9mlN	4.42000000	TJUVG55oAjgVdG2A1Z8MpyfLbpCWTUMiQh	usdt_trc_20	\N	2024-09-22 02:37:22	2024-09-22 02:37:22
13	WP-cwUwK6rEcp	5.20000000	2200300516861067	usdt_trc_20	\N	2024-09-22 04:39:51	2024-09-22 04:39:51
14	WP-gwbWCM6ETt	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-09-22 06:22:25	2024-09-22 06:22:25
15	WP-bIAfffQw7u	3.54000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-09-22 06:38:48	2024-09-22 06:38:48
16	WP-hQ4azLjK5p	3.45460000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-09-22 07:40:36	2024-09-22 07:40:36
17	WP-qwPvsuDRmn	2.54640000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-09-29 06:13:20	2024-09-29 06:13:20
18	WP-rGaj1gDxER	2.36000000	TXbiFxGtMGsFd4HEvnD8xYTtSBT5nahmYX	usdt_trc_20	\N	2024-09-29 07:57:46	2024-09-29 07:57:46
19	WP-UOtoFaDKLu	12.32000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-09-29 08:17:06	2024-09-29 08:17:06
20	WP-nd1Ec0hzlM	22.00000000	89825157616	usdt_trc_20	\N	2024-09-29 16:54:47	2024-09-29 16:54:47
21	WP-BqAtJdPbrC	6.24000000	2200152958349485	usdt_trc_20	\N	2024-09-29 18:30:34	2024-09-29 18:30:34
22	WP-i6Z8YCHh72	4.04000000	2200152958349485	usdt_trc_20	\N	2024-09-29 18:32:25	2024-09-29 18:32:25
23	WP-AJwsyRQNPx	2.49600000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-10-06 08:58:08	2024-10-06 08:58:08
24	WP-Tpl0G5avln	2.24000000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-10-06 09:12:43	2024-10-06 09:12:43
25	WP-vgCPb4ULrH	3.24000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-10-06 17:01:17	2024-10-06 17:01:17
26	WP-DA4LSAqHiX	11.40000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-10-06 21:12:58	2024-10-06 21:12:58
27	WP-SjW64TokfD	2.49600000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-10-13 06:31:37	2024-10-13 06:31:37
28	WP-T7hs0FmxIh	22.00000000	89825157616  ВТБ	usdt_trc_20	\N	2024-10-13 07:14:21	2024-10-13 07:14:21
29	WP-NTsoBTbI45	4.88000000	2200300516861067	usdt_trc_20	\N	2024-10-13 13:56:46	2024-10-13 13:56:46
30	WP-22War0RmhU	4.00000000	TScNUkuZHjU1kokLyqizyitQ4ypmJVgGvG	usdt_trc_20	\N	2024-10-13 15:25:54	2024-10-13 15:25:54
31	WP-ZTZPqSYhRG	2.30000000	TZFcTP8h5C91MHZACn9NvNdtHyLypsd99C	usdt_trc_20	\N	2024-10-20 05:33:53	2024-10-20 05:33:53
32	WP-0lnkrT2aHZ	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-10-20 07:26:15	2024-10-20 07:26:15
33	WP-Xbx7mu67V5	2.48000000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-10-20 09:36:13	2024-10-20 09:36:13
34	WP-sRddjidiiz	4.96000000	TJUVG55oAjgVdG2A1Z8MpyfLbpCWTUMiQh	usdt_trc_20	\N	2024-10-20 10:54:39	2024-10-20 10:54:39
35	WP-QZ7vDwxsxk	3.28000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-10-20 12:12:21	2024-10-20 12:12:21
36	WP-oN9MAAaOOU	2.24540000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-10-20 13:23:15	2024-10-20 13:23:15
37	WP-2zPvaBgwL7	2.30000000	TEUSfierbgAZQ1s8L7FsobV4NPB1eQzG2E	usdt_trc_20	\N	2024-10-20 13:40:58	2024-10-20 13:40:58
38	WP-gU0atu65rH	4.78000000	2200240283877799	usdt_trc_20	\N	2024-10-20 13:58:59	2024-10-20 13:58:59
39	WP-nLOU1G7AB8	6.62000000	2200240283877799	usdt_trc_20	\N	2024-10-20 14:04:20	2024-10-20 14:04:20
40	WP-tdi6UPNBfC	3.88000000	2200240283877799	usdt_trc_20	\N	2024-10-20 14:11:27	2024-10-20 14:11:27
41	WP-E2eWdMllud	4.76000000	2200240283877799	usdt_trc_20	\N	2024-10-20 14:17:49	2024-10-20 14:17:49
42	WP-8xRauYtwVd	11.40000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-10-27 04:04:01	2024-10-27 04:04:01
43	WP-qPAes8Fc22	2.51520000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-10-27 14:28:08	2024-10-27 14:28:08
44	WP-DF894pjbyx	4.06000000	TSoygNjsRHYxrCfcGno6XXy8y7dnciQ9Zr	usdt_trc_20	\N	2024-10-27 15:51:24	2024-10-27 15:51:24
45	WP-qpDWJLstbC	8.50000000	TSoygNjsRHYxrCfcGno6XXy8y7dnciQ9Zr	usdt_trc_20	\N	2024-10-27 16:02:57	2024-10-27 16:02:57
46	WP-1InPe48UGR	4.98000000	TZFcTP8h5C91MHZACn9NvNdtHyLypsd99C	usdt_trc_20	\N	2024-11-03 10:11:18	2024-11-03 10:11:18
47	WP-7SlQDOskZE	3.30000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-11-03 12:33:49	2024-11-03 12:33:49
48	WP-U5iD0ZfpeR	2.52700000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-11-03 17:08:48	2024-11-03 17:08:48
49	WP-KCIhA24JMt	3.62000000	TJUVG55oAjgVdG2A1Z8MpyfLbpCWTUMiQh	usdt_trc_20	\N	2024-11-10 02:47:37	2024-11-10 02:47:37
50	WP-2axJPSuZZG	2.99200000	TYNchrreijDyZwyLpC1GqrmMwqSSWE41iw	usdt_trc_20	\N	2024-11-10 06:37:06	2024-11-10 06:37:06
51	WP-fWtRNv7sns	2.56000000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-11-17 07:25:54	2024-11-17 07:25:54
52	WP-cJmwA7Qxay	4.84000000	TZFcTP8h5C91MHZACn9NvNdtHyLypsd99C	usdt_trc_20	\N	2024-11-17 07:43:24	2024-11-17 07:43:24
53	WP-Hdl3owozj5	30.17040000	TRupxJgKpPXVZxJ1H4Qrdx4vEd4scGfKgo	usdt_trc_20	\N	2024-11-17 08:27:25	2024-11-17 08:27:25
54	WP-bgbWN0FS9o	4.18000000	2200 2404 1838 8084	usdt_trc_20	\N	2024-11-17 11:41:14	2024-11-17 11:41:14
55	WP-X7Jlku9Fnd	3.34000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-11-17 13:22:15	2024-11-17 13:22:15
56	WP-KYaHMp5KvT	3.48000000	2200 1506 3994 2314	usdt_trc_20	\N	2024-11-17 19:52:34	2024-11-17 19:52:34
57	WP-eUIovfLflA	5.60000000	2200 1506 3994 2314	usdt_trc_20	\N	2024-11-17 19:57:38	2024-11-17 19:57:38
58	WP-iIL8xyF8Mg	4.16000000	2200 1506 3994 2314	usdt_trc_20	\N	2024-11-17 20:02:59	2024-11-17 20:02:59
59	WP-4jz2LnfOSH	4.16000000	2200 1506 3994 2314	usdt_trc_20	\N	2024-11-17 20:09:42	2024-11-17 20:09:42
60	WP-EApxdk3Pg6	4.06000000	2200154534594667	usdt_trc_20	\N	2024-11-17 20:27:26	2024-11-17 20:27:26
61	WP-vDJ2Wx4xvI	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-11-24 02:57:48	2024-11-24 02:57:48
62	WP-vHAaaOEpIr	4.10000000	2200 2459 1092 5010 ВТБ	usdt_trc_20	\N	2024-11-24 05:06:41	2024-11-24 05:06:41
63	WP-91TeiP8vjY	2.28500000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-11-24 05:58:06	2024-11-24 05:58:06
64	WP-iB73duekQa	3.48000000	TJUVG55oAjgVdG2A1Z8MpyfLbpCWTUMiQh	usdt_trc_20	\N	2024-11-24 07:58:57	2024-11-24 07:58:57
65	WP-s2WFd5qzhQ	8.86000000	2200152958349485	usdt_trc_20	\N	2024-11-24 13:08:24	2024-11-24 13:08:24
66	WP-DYrYVABTXC	4.06000000	2200152958349485	usdt_trc_20	\N	2024-11-24 13:10:10	2024-11-24 13:10:10
67	WP-ZIRiSH6Cnv	3.34000000	2202201165446770	usdt_trc_20	\N	2024-11-24 19:28:40	2024-11-24 19:28:40
68	WP-sbllhxc2qI	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-12-01 03:48:34	2024-12-01 03:48:34
69	WP-MPCoIQlZbr	3.36000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-12-01 10:32:33	2024-12-01 10:32:33
70	WP-6YDFmMloRP	8.46000000	89825157616 ВТБ	usdt_trc_20	\N	2024-12-01 16:11:47	2024-12-01 16:11:47
71	WP-Fm8EavJLC9	2.57300000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-12-08 04:03:07	2024-12-08 04:03:07
72	WP-Wm6dBmW4gB	2.80000000	40817810704000111772	usdt_trc_20	\N	2024-12-08 07:22:56	2024-12-08 07:22:56
73	WP-Fh5rtcNxSA	8.00000000	TLCU3GUsCGJeLobtj2nTvKnKLsxsmjqh3W	usdt_trc_20	\N	2024-12-08 15:38:46	2024-12-08 15:38:46
74	WP-pMKMBG3OFw	2.38000000	2200152958349485	usdt_trc_20	\N	2024-12-08 18:00:02	2024-12-08 18:00:02
75	WP-mruEJqc75P	2.48000000	2200152958349485	usdt_trc_20	\N	2024-12-08 18:04:20	2024-12-08 18:04:20
76	WP-SS4OlRflKm	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-12-15 12:18:33	2024-12-15 12:18:33
77	WP-HdBDONFbOF	3.40000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-12-15 14:35:12	2024-12-15 14:35:12
78	WP-0K0sXtjIIs	6.14000000	2202205386299686	usdt_trc_20	\N	2024-12-15 15:39:47	2024-12-15 15:39:47
79	WP-L0gIxSeMLs	4.10000000	2200154534594667	usdt_trc_20	\N	2024-12-15 16:08:53	2024-12-15 16:08:53
80	WP-eDDZCFcILy	4.18000000	4276040023848992	usdt_trc_20	\N	2024-12-15 18:56:19	2024-12-15 18:56:19
81	WP-DuPderBkxD	5.64000000	4276040023848992	usdt_trc_20	\N	2024-12-15 19:01:07	2024-12-15 19:01:07
82	WP-QNaQ6Nuj31	3.48000000	4276040023848992	usdt_trc_20	\N	2024-12-15 19:03:22	2024-12-15 19:03:22
83	WP-AWAuN5q1aH	4.20000000	4276040023848992	usdt_trc_20	\N	2024-12-15 19:06:28	2024-12-15 19:06:28
84	WP-A18SRmV6bc	2.28220000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-12-15 21:08:38	2024-12-15 21:08:38
85	WP-dFFTJSI5Ip	11.38000000	TRupxJqKpPXVZxJ1H4Qrdx4vEd4scGfKqo	usdt_trc_20	\N	2024-12-22 03:43:50	2024-12-22 03:43:50
86	WP-Tg5H8ozuTg	11.34000000	TZFcTP8h5C91MHZACn9NvNdtHyLypsd99C	usdt_trc_20	\N	2024-12-22 05:47:00	2024-12-22 05:47:00
87	WP-jFJNYFa58F	2.84000000	TPpxntMiadwiUFfXRwifT3AUZQMs1Va6e9	usdt_trc_20	\N	2024-12-22 07:58:51	2024-12-22 07:58:51
88	WP-yP9T6DJyC0	6.30000000	TJUVG55oAjgVdG2A1Z8MpyfLbpCWTUMiQh	usdt_trc_20	\N	2024-12-22 11:57:14	2024-12-22 11:57:14
89	WP-dQSGLDLrOK	3.60000000	2200152958349485	usdt_trc_20	\N	2024-12-22 14:26:56	2024-12-22 14:26:56
90	WP-ySQMxaOJ6o	6.62000000	2200152958349485	usdt_trc_20	\N	2024-12-22 16:21:47	2024-12-22 16:21:47
91	WP-tJ13saexJF	16.34000000	89825157616  Т-Банк	usdt_trc_20	\N	2024-12-22 20:05:32	2024-12-22 20:05:32
92	WP-qmcxyLH7Ey	4.30000000	2202201165446770	usdt_trc_20	\N	2024-12-29 06:18:19	2024-12-29 06:18:19
93	WP-Tj6AzE233C	3.40000000	TScNUkuZHjU1kokLyqizyitQ4ypmJVgGvG	usdt_trc_20	\N	2024-12-29 09:53:03	2024-12-29 09:53:03
94	WP-1YVHWUEloS	2.73800000	TY3gqXLWPH8eDsiDSNWYgnSrin99LM9oQV	usdt_trc_20	\N	2024-12-29 10:20:42	2024-12-29 10:20:42
95	WP-aPZBUgNLEf	3.38000000	40817810704000111772	usdt_trc_20	\N	2024-12-29 11:11:15	2024-12-29 11:11:15
96	WP-cB3OGtkztw	3.44000000	TYHB8BdLHJ3owJ44zTj8145VJasQmKqDkX	usdt_trc_20	\N	2024-12-29 11:13:19	2024-12-29 11:13:19
97	WP-NJKy4kDZMq	5.08000000	TLf3aGr6AJhpb8qNJFQ6Pa1hNdsLm2pri2	usdt_trc_20	\N	2024-12-29 12:52:36	2024-12-29 12:52:36
98	WP-wUo59pZh8f	2.56400000	TK34BWMpJw7RFU7sr2F4om5LZRq1Lv1DRw	usdt_trc_20	\N	2024-12-29 12:53:13	2024-12-29 12:53:13
99	WP-FfQbj07awf	4.30000000	2202201165446770	usdt_trc_20	\N	2025-01-05 01:33:53	2025-01-05 01:33:53
100	WP-suFVpYJVlJ	4.20000000	TW5nwd44e1Buvex5Tm6WBLVmezW7M94DSM	usdt_trc_20	\N	2025-01-05 14:01:08	2025-01-05 14:01:08
101	WP-KX8BSh80Ze	3.10000000	2200240283877799	usdt_trc_20	\N	2025-01-05 18:32:03	2025-01-05 18:32:03
102	WP-uEalgudwVN	4.72000000	2200240283877799	usdt_trc_20	\N	2025-01-05 18:35:57	2025-01-05 18:35:57
103	WP-Coki5s794b	3.64000000	2200240283877799	usdt_trc_20	\N	2025-01-05 18:39:21	2025-01-05 18:39:21
104	WP-k9V3OVGHdv	3.62000000	2200240283877799	usdt_trc_20	\N	2025-01-05 18:56:40	2025-01-05 18:56:40
\.


--
-- Name: deposit_gifts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deposit_gifts_id_seq', 1, false);


--
-- Name: deposits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deposits_id_seq', 67, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: itc_packages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.itc_packages_id_seq', 96, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 21, true);


--
-- Name: moonshine_socialites_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.moonshine_socialites_id_seq', 1, false);


--
-- Name: moonshine_user_roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.moonshine_user_roles_id_seq', 1, false);


--
-- Name: moonshine_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.moonshine_users_id_seq', 1, true);


--
-- Name: package_profit_reinvests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_profit_reinvests_id_seq', 536, true);


--
-- Name: package_profit_withdraws_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_profit_withdraws_id_seq', 389, true);


--
-- Name: package_profits_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_profits_id_seq', 1753, true);


--
-- Name: package_reinvests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_reinvests_id_seq', 1, false);


--
-- Name: package_withdraws_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_withdraws_id_seq', 1, false);


--
-- Name: partners_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.partners_id_seq', 40, true);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 872, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 61, true);


--
-- Name: withdraws_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.withdraws_id_seq', 104, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: deposit_gifts deposit_gifts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposit_gifts
    ADD CONSTRAINT deposit_gifts_pkey PRIMARY KEY (id);


--
-- Name: deposit_gifts deposit_gifts_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposit_gifts
    ADD CONSTRAINT deposit_gifts_uuid_unique UNIQUE (uuid);


--
-- Name: deposits deposits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposits
    ADD CONSTRAINT deposits_pkey PRIMARY KEY (id);


--
-- Name: deposits deposits_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deposits
    ADD CONSTRAINT deposits_uuid_unique UNIQUE (uuid);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: itc_packages itc_packages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.itc_packages
    ADD CONSTRAINT itc_packages_pkey PRIMARY KEY (id);


--
-- Name: itc_packages itc_packages_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.itc_packages
    ADD CONSTRAINT itc_packages_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: moonshine_socialites moonshine_socialites_driver_identity_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_socialites
    ADD CONSTRAINT moonshine_socialites_driver_identity_unique UNIQUE (driver, identity);


--
-- Name: moonshine_socialites moonshine_socialites_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_socialites
    ADD CONSTRAINT moonshine_socialites_pkey PRIMARY KEY (id);


--
-- Name: moonshine_user_roles moonshine_user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_user_roles
    ADD CONSTRAINT moonshine_user_roles_pkey PRIMARY KEY (id);


--
-- Name: moonshine_users moonshine_users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_users
    ADD CONSTRAINT moonshine_users_email_unique UNIQUE (email);


--
-- Name: moonshine_users moonshine_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_users
    ADD CONSTRAINT moonshine_users_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: package_profit_reinvests package_profit_reinvests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_reinvests
    ADD CONSTRAINT package_profit_reinvests_pkey PRIMARY KEY (id);


--
-- Name: package_profit_reinvests package_profit_reinvests_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_reinvests
    ADD CONSTRAINT package_profit_reinvests_uuid_unique UNIQUE (uuid);


--
-- Name: package_profit_withdraws package_profit_withdraws_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_withdraws
    ADD CONSTRAINT package_profit_withdraws_pkey PRIMARY KEY (id);


--
-- Name: package_profit_withdraws package_profit_withdraws_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profit_withdraws
    ADD CONSTRAINT package_profit_withdraws_uuid_unique UNIQUE (uuid);


--
-- Name: package_profits package_profits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profits
    ADD CONSTRAINT package_profits_pkey PRIMARY KEY (id);


--
-- Name: package_profits package_profits_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_profits
    ADD CONSTRAINT package_profits_uuid_unique UNIQUE (uuid);


--
-- Name: package_reinvests package_reinvests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_reinvests
    ADD CONSTRAINT package_reinvests_pkey PRIMARY KEY (id);


--
-- Name: package_reinvests package_reinvests_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_reinvests
    ADD CONSTRAINT package_reinvests_uuid_unique UNIQUE (uuid);


--
-- Name: package_withdraws package_withdraws_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_withdraws
    ADD CONSTRAINT package_withdraws_pkey PRIMARY KEY (id);


--
-- Name: package_withdraws package_withdraws_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_withdraws
    ADD CONSTRAINT package_withdraws_uuid_unique UNIQUE (uuid);


--
-- Name: partners partners_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.partners
    ADD CONSTRAINT partners_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_uuid_unique UNIQUE (uuid);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: withdraws withdraws_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.withdraws
    ADD CONSTRAINT withdraws_pkey PRIMARY KEY (id);


--
-- Name: withdraws withdraws_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.withdraws
    ADD CONSTRAINT withdraws_uuid_unique UNIQUE (uuid);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: package_profit_reinvests_package_uuid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_profit_reinvests_package_uuid_index ON public.package_profit_reinvests USING btree (package_uuid);


--
-- Name: package_profit_withdraws_package_uuid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_profit_withdraws_package_uuid_index ON public.package_profit_withdraws USING btree (package_uuid);


--
-- Name: package_profits_package_uuid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_profits_package_uuid_index ON public.package_profits USING btree (package_uuid);


--
-- Name: package_reinvests_package_uuid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_reinvests_package_uuid_index ON public.package_reinvests USING btree (package_uuid);


--
-- Name: package_withdraws_package_uuid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_withdraws_package_uuid_index ON public.package_withdraws USING btree (package_uuid);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: transactions_balance_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_balance_type_index ON public.transactions USING btree (balance_type);


--
-- Name: transactions_trx_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_trx_type_index ON public.transactions USING btree (trx_type);


--
-- Name: transactions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_user_id_index ON public.transactions USING btree (user_id);


--
-- Name: moonshine_users moonshine_users_moonshine_user_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.moonshine_users
    ADD CONSTRAINT moonshine_users_moonshine_user_role_id_foreign FOREIGN KEY (moonshine_user_role_id) REFERENCES public.moonshine_user_roles(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

