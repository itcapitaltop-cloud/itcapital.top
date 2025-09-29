<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('landing_page_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    @vite('resources/academy/style.css')
</head>
<body>
<!-- Шапка -->
<header class="header">
    <div class="header-container">
        <div class="logo">
            <img src="{{ vite()->academy('logo.svg') }}" alt="IT Academy Logo">
        </div>
        <nav class="nav">
            <a href="#step1" class="nav-link">1: {{ __('landing_step1_title') }}</a>
            <a href="#step2" class="nav-link">2: {{ __('landing_step2_title') }}</a>
        </nav>
        <div class="header-button">
            <a href="#start" class="btn-primary">{{ __('landing_btn_start_training') }}</a>
        </div>
    </div>
</header>

<!-- Второй логотип -->
<section class="second-logo-section">
    <div class="second-logo-container">
        <img src="{{ vite()->academy('itc_logo.svg') }}" alt="IT Capital Logo" class="second-logo">
        <p class="presents-text">{{ __('represents') }}</p>
    </div>
</section>

<!-- Изображение bootcamp -->
<section class="bootcamp-image-section">
    <div class="bootcamp-image-container">
        <img src="{{ vite()->academy('bootcamp.png') }}" alt="Trading Bootcamp" class="bootcamp-image">
    </div>
</section>

<!-- Основной контент -->
<main class="main">
    <div class="hero-section">
        <div class="hero-content">
            <p class="hero-subtitle">{{ __('landing_hero_subtitle') }}.</p>
            <p class="hero-description">{{ __('landing_hero_description') }}</p>
            <div class="hero-button">
                <div class="button-container">
                    <div class="relative group">
                        <a href="#start">
                                <span class="pointer-events-none">
                                    <svg width="66" height="64" fill="none" xmlns="http://www.w3.org/2000/svg"
                                         class="animate-shine-sweep will-change-transform" viewBox="0 0 66 64">
                                        <path d="M65.4562 0H34.4714L0.815552 64H35.0057L65.4562 0Z"
                                              fill="url(#paint0_linear_201_30)"/>
                                        <defs>
                                            <linearGradient id="paint0_linear_201_30" x1="18.979" y1="32" x2="47.1687"
                                                            y2="45.3862" gradientUnits="userSpaceOnUse">
                                                <stop offset="0" stop-color="#B4FF59"/>
                                                <stop offset="0.5" stop-color="#FFFFFF"/>
                                                <stop offset="1" stop-color="#B4FF59"/>
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </span>
                            <span class="relative">{{ __('landing_hero_button') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Блок "База трейдинга" -->
<section class="trading-base-section">
    <div class="trading-base-container">
        <div class="trading-base-header" id="step1">
            <h2 class="trading-base-title">{{ __('landing_step1_title') }}</h2>
            <span class="trading-base-step">{{ __('landing_step1_step') }}</span>
        </div>

        <!-- Типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block1_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block1_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block1_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block1_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Второй типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block2_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block2_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block2_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block2_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block2_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block2_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Третий типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block3_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block3_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block3_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block2_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block3_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Четвертый типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block4_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block4_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block4_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block2_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block4_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Пятый типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block5_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block5_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block5_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block5_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Дополнительные преимущества курса -->
<section class="advantages-section">
    <div class="advantages-container">
        <h2 class="advantages-title">{{ __('landing_advantages_base_title') }}</h2>

        <div class="advantages-content">
            <div class="advantages-list">
                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-number">10</span>
                        <span class="advantage-label">{{ __('landing_classes') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_access_to_records') }} —</span>
                        <span class="advantage-number">6</span>
                        <span class="advantage-label">{{ __('landing_months') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-number">{{ __('landing_textbook') }}</span>
                        <span class="advantage-label">{{ __('landing_on_trade') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-number">2</span>
                        <span class="advantage-label">{{ __('landing_practices_with_curator') }}</span>
                    </div>
                </div>
            </div>

            <div class="pricing-section">
                <div class="pricing-info">
                    <span class="cost-label">{{ __('landing_price') }} —</span>
                    <span class="cost-amount">₽70.000</span>
                </div>
                <a href="#start" class="btn-primary">{{ __('landing_start_training') }}</a>
            </div>
        </div>
    </div>
</section>

<!-- Блок "Проп-трейдер профи" -->
<section class="trading-base-section">
    <div class="trading-base-container">
        <div class="trading-base-header" id="step2">
            <h2 class="trading-base-title">{{ __('landing_step2_title') }}</h2>
            <span class="trading-base-step">{{ __('landing_step2_step') }}</span>
        </div>

        <!-- Типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block6_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block6_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block6_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block6_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Второй типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block7_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block7_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block7_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block7_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Третий типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block8_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block8_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block8_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block8_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Четвертый типовой блок с описанием -->
        <div class="course-block">
            <div class="course-block-header">
                <h3 class="course-block-title">{{ __('landing_block9_title') }}</h3>
                <p class="course-block-subtitle">{{ __('landing_block9_subtitle') }}</p>
            </div>

            <div class="course-block-content">
                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_about_title') }}</h4>
                    <ul class="course-list bullet1-list">
                        @foreach(__('landing_block9_about_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="course-block-section">
                    <h4 class="section-title">{{ __('landing_block1_why_title') }}</h4>
                    <ul class="course-list bullet2-list">
                        @foreach(__('landing_block9_why_items') as $items)
                            <li>{{ $items }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Дополнительные преимущества курса -->
<section class="advantages-section">
    <div class="advantages-container">
        <h2 class="advantages-title">{{ __('landing_advantages_prof_title') }}</h2>

        <div class="advantages-content">
            <div class="advantages-list">
                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-number">15</span>
                        <span class="advantage-label">{{ __('landing_classes') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_access_to_records') }} —</span>
                        <span class="advantage-number">12</span>
                        <span class="advantage-label">{{ __('landing_months') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-number">{{ __('landing_textbook') }}</span>
                        <span class="advantage-label">{{ __('landing_secrets_of_successful_prop_trading') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_chat') }}</span>
                        <span class="advantage-number">{{ __('landing_training_and_homework') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_curatorial') }}</span>
                        <span class="advantage-number">{{ __('landing_curatorial_support') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_traders_chat') }}</span>
                        <span class="advantage-number">1</span>
                        <span class="advantage-label">{{ __('landing_once_week') }}</span>
                    </div>
                </div>

                <div class="advantage-item">
                    <div class="advantage-icon">✓</div>
                    <div class="advantage-text">
                        <span class="advantage-label">{{ __('landing_daily') }}</span>
                        <span class="advantage-number">{{ __('landing_market_analytics') }}</span>
                    </div>
                </div>
            </div>

            <div class="pricing-section">
                <div class="pricing-info">
                    <span class="cost-label">{{ __('landing_price') }} —</span>
                    <span class="cost-amount">₽160.000</span>
                </div>
                <a href="#start" class="btn-primary">{{ __('landing_btn_start_training') }}</a>
            </div>
        </div>
    </div>
</section>

<!-- Баннер с таймером -->
<div class="discount-banner">
    <div class="discount-content">
        <div class="discount-image">
            <img src="{{ vite()->academy('discount.png') }}" alt="Скидка">
        </div>
        <div class="discount-info">
            <h3 class="discount-title">{{ __('landing_discount_title') }}</h3>
            <div class="countdown-timer">
                <div class="timer-item">
                    <span class="timer-number" id="hours">48</span>
                    <span class="timer-label">{{ __('landing_hours') }}</span>
                </div>
                <div class="timer-separator">:</div>
                <div class="timer-item">
                    <span class="timer-number" id="minutes">00</span>
                    <span class="timer-label">{{ __('landing_minute') }}</span>
                </div>
                <div class="timer-separator">:</div>
                <div class="timer-item">
                    <span class="timer-number" id="seconds">00</span>
                    <span class="timer-label">{{ __('landing_seconds') }}</span>
                </div>
            </div>
        </div>
    </div>
    <button class="discount-close" onclick="closeDiscountBanner()">×</button>
</div>

<!-- Модальное окно с формой -->
<div class="modal-overlay" id="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">×</button>

        <div class="modal-header">
            <img src="{{ vite()->academy('study.png') }}" alt="Обучение" class="modal-image">
            <h2 class="modal-title">{{ __('landing_modal_title') }}</h2>
            <p class="modal-subtitle">{{ __('landing_modal_subtitle') }}</p>
        </div>

        <form class="modal-form" id="registrationForm" action="https://formspree.io/f/xrblodke" method="POST">
            <input type="hidden" name="_subject" value="Заявка на обучение Trading Bootcamp">
            <input type="hidden" name="_replyto" value="armbrothers28@gmail.com">
            <input type="hidden" name="_cc" value="armbrothers28@gmail.com">
            <input type="hidden" name="_next" value="https://yourdomain.com/thanks.html">

            <div class="form-group">
                <label for="firstName">{{ __('landing_form_name') }}</label>
                <input type="text" id="firstName" name="firstName" required>
            </div>

            <div class="form-group">
                <label for="lastName">{{ __('landing_form_lastname') }}</label>
                <input type="text" id="lastName" name="lastName" required>
            </div>

            <div class="form-group">
                <label for="phone">{{ __('landing_form_phone') }}</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div class="form-group">
                <label for="telegram">{{ __('landing_form_telegram') }}</label>
                <input type="text" id="telegram" name="telegram" placeholder="@username" required>
            </div>

            <button type="submit" class="form-submit">{{ __('landing_form_submit') }}</button>
        </form>
    </div>
</div>

<!-- Подвал -->
<footer class="footer">
    <div class="footer-container">
        <p class="footer-text">
            <span class="copyright-symbol" onclick="openAdmin()">©</span> 2025 IT Capital Academy
        </p>
        <a href="{{ route('academy.admin') }}" class="admin-link" style="display: none;">{{ __('landing_admin') }}</a>
    </div>
</footer>

<script>
    const i18n = {
        landing_hero_subtitle: @json(__('landing_hero_subtitle')),
        landing_hero_subtitle_html: @json(__('landing_hero_subtitle_html')),
        landing_submit_js: @json(__('landing_submit_js')),
        landing_modal_thanks: @json(__('landing_modal_thanks')),
        landing_modal_thanks_error: @json(__('landing_modal_thanks_error'))
    };

    // Функция для адаптивного переноса текста
    function adjustTextBreak() {
        const subtitle = document.querySelector('.hero-subtitle');
        if (subtitle && window.innerWidth <= 768) {
            subtitle.innerHTML = i18n.landing_hero_subtitle_html;
        } else if (subtitle && window.innerWidth > 768) {
            subtitle.innerHTML = i18n.landing_hero_subtitle;
        }
    }

    // Таймер обратного отсчета
    function startCountdown() {
        // Загружаем настройки из админки
        const savedSettings = localStorage.getItem('timerSettings');
        let endDate;

        if (savedSettings) {
            const settings = JSON.parse(savedSettings);
            endDate = new Date(settings.endDate);

            // Учитываем часовой пояс
            if (settings.timezone === 'UTC') {
                // Конвертируем в UTC
                endDate = new Date(endDate.getTime() - endDate.getTimezoneOffset() * 60000);
            }
        } else {
            // Значение по умолчанию
            endDate = new Date('2025-08-09T23:59:59');
        }

        function updateTimer() {
            const now = new Date().getTime();
            const distance = endDate.getTime() - now;

            if (distance < 0) {
                // Время истекло
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                return;
            }

            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    // Функция закрытия баннера
    function closeDiscountBanner() {
        const banner = document.querySelector('.discount-banner');
        if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'scale(0.8)';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 300);
        }
    }

    // Функция открытия модального окна
    function openModal() {
        const modal = document.getElementById('modal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }

    // Функция закрытия модального окна
    function closeModal() {
        const modal = document.getElementById('modal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    // Обработка отправки формы
    function handleFormSubmit(event) {
        console.log('Функция handleFormSubmit вызвана!');
        event.preventDefault();

        const form = event.target;
        const submitButton = form.querySelector('.form-submit');
        const originalText = submitButton.textContent;

        // Показываем состояние загрузки
        submitButton.textContent = i18n.landing_submit_js;
        submitButton.disabled = true;

        // Создаем FormData для отправки
        const formData = new FormData(form);

        // Логируем данные для отладки
        console.log('Отправляем данные:', Object.fromEntries(formData));

        // Отправляем форму через Formspree
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (response.ok) {
                    // Успешная отправка
                    console.log('Форма успешно отправлена!');
                    alert(i18n.landing_modal_thanks);
                    closeModal();
                    form.reset();
                } else {
                    return response.text().then(text => {
                        console.log('Error response:', text);
                        throw new Error('Ошибка отправки: ' + response.status);
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert(i18n.landing_modal_thanks_error);
            })
            .finally(() => {
                // Восстанавливаем кнопку
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
    }

    // Вызываем функции при загрузке
    window.addEventListener('load', function () {
        adjustTextBreak();
        startCountdown();

        // Добавляем обработчики для кнопок "Начать обучение"
        const startButtons = document.querySelectorAll('a[href="#start"]');
        console.log('Найдено кнопок "Начать обучение":', startButtons.length);
        startButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                console.log('Кнопка "Начать обучение" нажата');
                e.preventDefault();
                openModal();
            });
        });

        // Добавляем обработчик для формы
        const form = document.getElementById('registrationForm');
        if (form) {
            console.log('Форма найдена, добавляем обработчик');
            form.addEventListener('submit', handleFormSubmit);
        } else {
            console.log('Форма не найдена!');
        }

        // Закрытие модального окна при клике на фон
        const modal = document.getElementById('modal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }
    });

    // Вызываем функцию при изменении размера окна
    window.addEventListener('resize', adjustTextBreak);

    // Функция для открытия админки
    window.openAdmin = function () {
        window.open(@json(route('academy.admin')), '_blank');
    };
</script>
</body>
</html>
