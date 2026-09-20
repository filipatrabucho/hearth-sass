# HearthGG - Backend

API Laravel do HearthGG: uma plataforma **multi-tenant** de gestão de comunidades Discord.
Cada Discord admin (cliente) faz login com a sua conta Discord e, consoante o plano
contratado, tem acesso a diferentes módulos (membros, eventos, bans, pedidos de unban,
tickets, ...) para gerir a sua comunidade. Como dona da plataforma, podes saltar entre
todos os clientes e controlar quais os módulos que cada um tem ativos.

O frontend (React) é um projeto à parte e ainda não foi iniciado - este repositório é só
a API.

## Stack

- Laravel 12 / PHP 8.2+
- MySQL/MariaDB (troca fácil para outro driver via `.env`)
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
  `plan`, `status`, `suspended_reason`, `stripe_customer_id`/`stripe_subscription_id`
  - estes dois últimos ainda não usados, ver secção "Pagamentos" abaixo).
- `client_user` - papel (`owner` / `admin` / `staff`) de cada utilizador num client.
- `modules` - catálogo de funcionalidades: `members`, `bans`, `events`, `tickets`,
  `posts`, `invites`, `analytics` (chaves iguais ao `ModuleKey` do frontend).
- `client_module` - quais os módulos estão ativos em cada client, com o estado de
  pagamento (`payment_status`: `trialing`/`active`/`past_due`/`canceled`, `paid_until`).
  Ver `App\Domain\Module\ClientModule::isActive()`.
- `members` / `invites` - cache local dos membros e convites do servidor de Discord de
  cada client, atualizado por `App\Services\Discord\MemberService::sync()` /
  `InviteService::sync()`.
- `warnings`, `tickets` + `ticket_messages`, `posts` - dados próprios da HearthGG,
  cada um com um efeito espelhado no Discord (DM de aviso, canal privado do ticket,
  mensagem publicada) tratado pelos serviços em `app/Services/*.php`.
- `leads` - sign-ups do formulário público "get started" do site de marketing (não
  são clients ainda - ver secção "Leads" abaixo).

## Leads

`POST /api/leads` é a única rota pública da API (fora do grupo `auth:sanctum` em
`routes/api.php`) - o formulário "get started" da homepage envia para aqui sem
sessão. Continua protegida pelo CSRF do Sanctum (o frontend faz sempre
`GET /sanctum/csrf-cookie` primeiro) e tem `throttle:10,1` contra spam.

Rever/gerir leads é trabalho da HearthGG, não de um client, por isso
`GET /api/leads` (com `?status=new|contacted|converted|archived`),
`PUT /api/leads/{lead}/status` e `DELETE /api/leads/{lead}` estão atrás de
`super_admin`.

## Pagamentos (quem tem acesso a quê)

Há dois níveis de controlo de acesso, ambos verificados por
`App\Http\Middleware\EnsureModuleAccess` (`->middleware('module:<key>')`) antes de
qualquer rota `/api/clients/{client}/...` correr:

1. **Conta do client** (`clients.status`: `active` / `suspended` / `cancelled`) - o
   interruptor geral. Um client suspenso perde acesso a *todos* os módulos,
   independentemente do que tem pago individualmente. Só um super admin HearthGG
   pode mudar isto:
   - `POST /api/clients/{client}/activate`
   - `POST /api/clients/{client}/suspend` (body opcional: `reason`)
   - `POST /api/clients/{client}/cancel`
   - `GET /api/clients?status=suspended` (ou `active`/`cancelled`) para veres
     rapidamente quem está a pagar e quem não está.
2. **Módulo individual** (`client_module.payment_status`/`paid_until`) - já
   documentado acima; controlado por `POST /api/clients/{client}/modules/{module}`.

Isto é tudo manual por agora (o super admin ativa/suspende à mão). Quando ligarmos
ao **Stripe**, os campos `stripe_customer_id`/`stripe_subscription_id` em `clients`
e a config em `config/services.php` (`STRIPE_KEY`/`STRIPE_SECRET`/
`STRIPE_WEBHOOK_SECRET`) já estão prontos - um webhook do Stripe só precisa de
chamar `Client::activate()`/`suspend()`/`cancel()` (ou `ClientRepository::setModuleEnabled()`
para um módulo específico) em vez de esperar por um super admin.

Super admins passam sempre por estas verificações (podem entrar num client
suspenso para o resolver); só o staff do próprio client é bloqueado.

## Acesso ao Discord de cada cliente

O acesso ao Discord de um client não passa por guardar um "token de acesso" por
cliente: a HearthGG tem **um único bot** (token em `DISCORD_BOT_TOKEN`) que os
donos dos servidores instalam no seu próprio Discord. `clients.bot_installed_at` /
`bot_permissions` registam esse instalação (`Client::recordBotInstall()`,
`POST /api/clients/{client}/bot/install` - chamado pelo frontend depois do admin
autorizar o bot no Discord). Todas as chamadas à API do Discord passam por:

- `app/Services/Discord/DiscordClient.php` - wrapper HTTP fino, autenticado como o
  bot (`Authorization: Bot ...`). Nenhum outro código fala diretamente com o Discord.
- `app/Services/Discord/*Service.php` - um serviço por módulo (`MemberService`,
  `RoleService`, `ChannelService`, `EventService`, `AnalyticsService`,
  `InviteService`, `MessageService`), cada um só com os endpoints do Discord
  relevantes a esse módulo.
- `App\Http\Middleware\EnsureModuleAccess` (`->middleware('module:<key>')`) - em
  toda a rota `/api/clients/{client}/...` que mexe num módulo. Confirma, por esta
  ordem: (1) o utilizador tem acesso ao client, (2) o módulo está pago/ativo
  (`Client::hasModuleEnabled()`), (3) o bot está mesmo instalado no servidor.

## Setup local

Cria primeiro a base de dados no MariaDB (nome igual ao `DB_DATABASE` do `.env`):

```sql
CREATE DATABASE hearthgg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Confirma no `.env` que `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME` e `DB_PASSWORD` correspondem ao teu MariaDB (por defeito assume
`root` sem password em `127.0.0.1:3306`, o normal numa instalação XAMPP).

Preenche no `.env` as credenciais da app Discord (criada no
[Discord Developer Portal](https://discord.com/developers/applications)):

```
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=http://localhost:8000/auth/discord/callback
DISCORD_BOT_TOKEN=
```

`DISCORD_BOT_TOKEN` é o token do bot da aplicação Discord (separado do OAuth de
login) - é ele que faz as chamadas à API do Discord em nome de cada client.

E aponta `FRONTEND_URL` / `SANCTUM_STATEFUL_DOMAINS` para onde o React vai correr.

### Tornares-te super admin (para testar)

1. Descobre o teu Discord user ID: no Discord, Definições → Avançadas → ativa o
   "Modo de Programador"; depois clica com o botão direito no teu avatar/nome em
   qualquer lado → "Copiar ID de Utilizador".
2. Põe esse ID em `SUPER_ADMIN_DISCORD_IDS` no `.env` (podes pôr vários,
   separados por vírgula).
3. Vai a `http://localhost:8000/auth/discord/redirect` no browser e autoriza a
   app. Ao voltar já és super admin - confirma com `GET /api/auth/me`
   (`is_super_admin` deve vir `true`).

Isto só promove, nunca despromove: se tirares o ID do `.env` mais tarde, uma
conta já promovida mantém o acesso (ver `DiscordAuthController::callback()`).

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
