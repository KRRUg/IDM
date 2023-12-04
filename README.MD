# Identity Management System for KLMS 

Identity management and authorization provider which is used by KLMS.

## Installation

```
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
# Required once migrations exist in src/Migrations
# php bin/console doctrine:migrations:migrate
#Load Fixtures
php bin/console doctrine:fixtures:load
```

## Fixtures
User Example:
- Nickname: User 1
- EMail: user1@localhost.local
- Password: user1

API Example:
- Name: Example
- APIKey: 1234

## Authentication
You need an API Token for all /api/ Endpoints! (except the Documentation)

Set the API-Token in the `X-API-KEY` Header

OpenAPI/Swagger Documentation is under /api/doc (or /api/doc.json)

## CLI Usage
Commands are all under app:

`php bin/console app:`

All Commands have a Helppage (e.g. `php bin/console app:apikeys:create -h`)

#### API Management (See the Helppages for the Commands)
Create an API-Key:
`php bin/console app:apikeys:create`  
List all API-Keys:
`php bin/console app:apikeys:list`  
Delete API-Key:
`php bin/console app:apikeys:delete`

#### User Management (See the Helppages for the Commands)
Create a User: `php bin/console app:user:create`  
Delete a User: `php bin/console app:user:delete`  
Disable a User: `php bin/console app:user:disable`  
Enable a User: `php bin/console app:user:enable`  
Edit a User: `php bin/console app:user:edit`  
List one/all Users: `php bin/console app:user:list`  