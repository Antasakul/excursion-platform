// Основные функции JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех интерактивных элементов
    
    // Плавная прокрутка для якорей
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Автоматическое скрытие уведомлений
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Валидация форм
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#27ae60';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Пожалуйста, заполните все обязательные поля');
            }
        });
    });
});

// Функция для загрузки доступных дат
function loadAvailableDates(excursionId, dateSelect) {
    fetch(`../api/get_dates.php?excursion_id=${excursionId}`)
        .then(response => response.json())
        .then(dates => {
            dateSelect.innerHTML = '<option value="">Выберите дату</option>';
            dates.forEach(date => {
                const option = document.createElement('option');
                option.value = date.id;
                option.textContent = `${date.available_date} ${date.available_time} (доступно мест: ${date.available_slots})`;
                dateSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Функция для расчета общей стоимости
function calculateTotalPrice(pricePerPerson, participants) {
    return pricePerPerson * participants;
}