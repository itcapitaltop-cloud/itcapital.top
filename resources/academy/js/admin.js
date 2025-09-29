// Данные для входа
const ADMIN_LOGIN = 'itcapital-admin';
const ADMIN_PASSWORD = 'hs37_8*983&2';

// Переменные для капчи
let captchaAnswer = 0;

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    generateCaptcha();
    loadTimerSettings();
    
    // Обработчик формы входа
    const loginForm = document.getElementById('adminLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Обработчик формы настроек таймера
    const settingsForm = document.getElementById('timerSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', handleTimerSettings);
    }
    
    // Обновление текущего таймера каждую секунду
    setInterval(updateCurrentTimer, 1000);
});

// Генерация капчи
function generateCaptcha() {
    const num1 = Math.floor(Math.random() * 10) + 1;
    const num2 = Math.floor(Math.random() * 10) + 1;
    captchaAnswer = num1 + num2;
    
    const captchaQuestion = document.getElementById('captchaQuestion');
    if (captchaQuestion) {
        captchaQuestion.textContent = `${num1} + ${num2} = ?`;
    }
}

// Обработка входа
function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const username = formData.get('username');
    const password = formData.get('password');
    const captcha = parseInt(formData.get('captcha'));
    
    // Проверка данных
    if (username === ADMIN_LOGIN && password === ADMIN_PASSWORD && captcha === captchaAnswer) {
        // Успешный вход
        localStorage.setItem('adminLoggedIn', 'true');
        showAdminPanel();
    } else {
        alert('Неверные данные для входа или капча!');
        generateCaptcha();
        event.target.reset();
    }
}

// Показать панель администратора
function showAdminPanel() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    loadTimerSettings();
}

// Выход из системы
function logout() {
    localStorage.removeItem('adminLoggedIn');
    location.reload();
}

// Загрузка настроек таймера
function loadTimerSettings() {
    const savedSettings = localStorage.getItem('timerSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        
        const endDateInput = document.getElementById('endDate');
        const timezoneSelect = document.getElementById('timezone');
        
        if (endDateInput && settings.endDate) {
            endDateInput.value = settings.endDate;
        }
        
        if (timezoneSelect && settings.timezone) {
            timezoneSelect.value = settings.timezone;
        }
    }
}

// Обработка настроек таймера
function handleTimerSettings(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const endDate = formData.get('endDate');
    const timezone = formData.get('timezone');
    
    // Сохранение настроек
    const settings = {
        endDate: endDate,
        timezone: timezone,
        timestamp: Date.now()
    };
    
    localStorage.setItem('timerSettings', JSON.stringify(settings));
    
    // Показываем сообщение об успехе
    const saveBtn = event.target.querySelector('.save-btn');
    const originalText = saveBtn.textContent;
    
    saveBtn.textContent = 'Сохранено!';
    saveBtn.disabled = true;
    
    setTimeout(() => {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    }, 2000);
    
    alert('Настройки таймера сохранены!');
}

// Обновление текущего таймера
function updateCurrentTimer() {
    const savedSettings = localStorage.getItem('timerSettings');
    if (!savedSettings) return;
    
    const settings = JSON.parse(savedSettings);
    const endDate = new Date(settings.endDate);
    const timezone = settings.timezone;
    
    let now;
    if (timezone === 'local') {
        now = new Date();
    } else if (timezone === 'UTC') {
        now = new Date();
        now = new Date(now.getTime() + now.getTimezoneOffset() * 60000);
    } else {
        // Для других часовых поясов используем локальное время
        now = new Date();
    }
    
    const distance = endDate.getTime() - now.getTime();
    
    if (distance < 0) {
        // Время истекло
        document.getElementById('currentHours').textContent = '00';
        document.getElementById('currentMinutes').textContent = '00';
        document.getElementById('currentSeconds').textContent = '00';
        return;
    }
    
    const hours = Math.floor(distance / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('currentHours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('currentMinutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('currentSeconds').textContent = seconds.toString().padStart(2, '0');
}

// Проверка авторизации при загрузке
function checkAuth() {
    const isLoggedIn = localStorage.getItem('adminLoggedIn') === 'true';
    if (isLoggedIn) {
        showAdminPanel();
    }
}

// Вызываем проверку авторизации
checkAuth();
