# HearthGG - Backend

API Laravel do HearthGG: uma plataforma **multi-tenant** de gestão de comunidades Discord.
Cada Discord admin (cliente) faz login com a sua conta Discord e, consoante o plano
contratado, tem acesso a diferentes módulos (membros, eventos, bans, pedidos de unban,
tickets, ...) para gerir a sua comunidade. Como dona da plataforma, podes saltar entre
todos os clientes e controlar quais os módulos que cada um tem ativos.

O frontend (React) é um projeto à parte e ainda não foi iniciado - este repositório é só
a API.

## Stack

- Laravel 13 / PHP 8.3+
- SQLite em desenvolvimento (troca fácil para MySQL/Postgres via `.env`)
- Laravel Sanctum (sessão SPA + tokens de API)
- Laravel Socialite + `socialiteproviders/discord` (login exclusivo via Discord OAuth)

## Estrutura (camadas)

- `app/Domain/*` - Eloquent models "magros" (`$guarded = []`), cada um com
  `validationRules()` e `activeFields()` (quais os campos do `request()` que podem ser
  escritos pelo serviço genérico).
- `app/Http/Controllers/Api/*` - controllers finos: validam com
  `$this->validate($request, $model->validationRules())` e delegam para
  `App\Services\ModelServiceApi` (create/update/destroy genéricos) ou para um
  Repository quando a lógica é mais pesada (joins, agregações, sync de pivots).
- `app/Services/AuthServiceApi.php` - resolve o cliente (tenant) do pedido atual e
  verifica permissões (`owner` > `admin` > `staff`); super admins da HearthGG têm
  sempre acesso.
- `app/Repositories/*` - queries/lógica mais pesada (ex.: `ClientRepository` trata de
  listar clientes por utilizador, ativar/desativar módulos, gerir membros).
- `routes/api.php` - tudo sob `auth:sanctum`.
- `routes/web.php` - só o fluxo de OAuth do Discord (precisa de sessão/cookies para o
  redirect do browser) e um endpoint de health-check.

## Modelo de dados (multi-tenant)

- `users` - utilizadores autenticados via Discord (`discord_id`, `username`, avatar,
  tokens OAuth, `is_super_admin` para as tuas próprias contas).
- `clients` - cada comunidade Discord onboarded (`discord_guild_id`, `owner_user_id`,
  `plan`, `status`).
- `client_user` - papel (`owner` / `admin` / `staff`) de cada utilizador num client.
- `modules` - catálogo de funcionalidades (`events`, `bans`, `ban_appeals`, `tickets`,
  `members`, ...).
- `client_module` - quais os módulos ativos (pagos) em cada client.

## Setup local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Preenche no `.env` as credenciais da app Discord (criada no
[Discord Developer Portal](https://discord.com/developers/applications)):

```
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=http://localhost:8000/auth/discord/callback
```

E aponta `FRONTEND_URL` / `SANCTUM_STATEFUL_DOMAINS` para onde o React vai correr.

## Fluxo de autenticação

1. Frontend redireciona o browser para `GET /auth/discord/redirect`.
2. Discord devolve o utilizador a `GET /auth/discord/callback`, que cria/atualiza o
   `User` e inicia sessão Sanctum (SPA), redirecionando de volta para `FRONTEND_URL`.
3. Pedidos seguintes à API (`/api/...`) autenticam-se pela cookie de sessão via
   `auth:sanctum`.

## Testes

```bash
php artisan test
```
