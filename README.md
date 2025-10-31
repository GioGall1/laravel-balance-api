# Тестовое задание Laravel Balance API

Простое REST API для управления балансом пользователей.  
Реализовано в рамках тестового задания на позицию PHP Junior Developer.

## Требования

- Установлены Docker и Docker Compose  
- PHP >= 8.1 (если запуск без Sail)  
- Composer

---

## Стек технологий

- PHP 8.3+
- Laravel 12
- PostgreSQL
- Docker (Laravel Sail)
- REST API (JSON)

---

## Основной функционал

- POST /api/deposit — пополнение баланса  
- POST /api/withdraw — списание средств  
- POST /api/transfer — перевод между пользователями  
- GET /api/balance/{user_id} — получение текущего баланса  

---

## Установка и запуск

# Клонируем проект
git clone https://github.com/USERNAME/laravel-balance-api.git
cd laravel-balance-api

# Устанавливаем зависимости
composer install

# Копируем пример .env
cp .env.example .env

# Запускаем docker
./vendor/bin/sail up -d

# Применяем миграции и сиды
./vendor/bin/sail artisan migrate --seed

---

## Логика работы

- Все операции выполняются в транзакциях (`DB::transaction()`).
- Баланс не может уходить в минус.
- Если у пользователя нет записи о балансе — она создаётся при первом пополнении.
- В таблице transactions сохраняются все операции:
  - deposit
  - withdraw
  - transfer_in
  - transfer_out

---

## Автор

Георгий  
Junior PHP Developer  
📍 Batumi, Georgia  
📧 m1g1oga11@gmail.com