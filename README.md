# erictest
Eric's test challenge

## Установка

Для установки понадобится docker, docker-compose, git. Используется TCP порт 8080. Команды выполнять из корня проекта, если не указано иное.

- Установить docker, docker-compose, git
- Клонировать репозиторий
```
git clone https://github.com/bblackcblackc/erictest.git
``` 
- Запустить контейнер с БД 
```
docker-compose up mysql
```
- Подождать инициализации БД
- Импортировать схему данных из init.sql в корне проекта. ПАРОЛЬ-БД -- root пароль от базы данных, можно найти в файле конфигурации docker-compose.yml

```
- Остановить контейнер mariadb и запустить все контейнеры
```
docker-compose down
docker-compose up
```
```

## Работа
Все скрипты доступны по адресу etest.localhost:8080,
http://etest.localhost:8080/dl/?url=xxx&md5=xxx для загрузки
http://etest.localhost:8080/st/ для статистики

