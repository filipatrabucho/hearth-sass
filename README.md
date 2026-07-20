# Hearth — plataforma de gestão de comunidades Discord

React 18 + Vite + React Router + Supabase (Auth/DB) + Netlify Functions.

## O que já está feito

**Site público** (`/`, `/regras`, `/equipa`, `/eventos`, `/updates`, `/suporte`)
- Landing page com hero (vídeo de fundo), prova social, grelha de features, novidades,
  eventos dinâmicos, sobre nós, parceiros e loja (Fourthwall widget/placeholder)
- `/regras` com progressão de convites Bronze → Emerald (+ progresso pessoal se tiveres sessão)
- `/equipa`, cartões de staff vindos do Supabase (só quem for marcado como visível)
- `/eventos`, calendário público (próximos e anteriores)
- `/updates`, changelog público
- `/suporte`, formulário de contacto (guarda o pedido na BD + email opcional via Resend)
- Identidade visual configurável por workspace: cores (fundo/destaque/texto), logo, banner,
  tagline, redes sociais — aplicada como CSS custom properties em todo o site e dashboard

**Autenticação e multi-tenant**
- Login com Discord (via Supabase Auth OAuth2)
- Perfil criado automaticamente no primeiro login (trigger na BD)
- Onboarding: criar workspace → convidar o bot Discord → sincronizar (stub, ver limitações)
- Convite de staff por email (usa o invite nativo do Supabase)

**Dashboard de staff**
- Layout com sidebar + topbar + workspace switcher
- `/dashboard/events`, CRUD real de eventos
- `/dashboard/posts`, CRUD de publicações/parceiros com `show_on_homepage`
- `/dashboard/updates`, publicar entradas de changelog
- `/dashboard/settings/team`, convidar staff + perfil público (título, bio, visibilidade em /equipa)
- `/dashboard/settings/branding`, identidade visual com pré-visualização em tempo real
- `/dashboard/settings/welcome`, configuração do welcome flow (ver limitações)
- Páginas placeholder para Analytics, Membros, Moderação, Tickets, XP & Níveis (dados reais
  dependem do bot Discord — ver abaixo)

## Limitações conhecidas / próximos passos

- **Bot Discord (discord.js, Fly.io) não está neste repositório.** É um serviço separado.
  Sem ele: sincronização de membros, welcome flow, auto-moderação, XP e publicação
  automática de eventos ficam só configuradas na BD, mas não são entregues no Discord.
  `netlify/functions/sync-workspace.js` é um stub que só marca `last_synced_at`.
- Analytics, Membros, Moderação, Tickets e XP continuam placeholder — precisam do bot
  para teres dados reais para mostrar.
- Stripe/billing (Fase 3 do roadmap) ainda não está implementado.
- Upload de logo/banner é por URL (sem Supabase Storage) por agora.

---

## Setup

### 1. Criar o projeto Supabase

1. Cria um projeto em [supabase.com](https://supabase.com)
2. Vai a **SQL Editor** e corre, por ordem, `supabase/schema.sql` e depois `supabase/002_public_site.sql`
3. Vai a **Authentication → URL Configuration** e adiciona:
   - Site URL: `http://localhost:5173` (e depois o teu domínio de produção)
   - Redirect URLs: `http://localhost:5173/auth/callback` (+ produção)

### 2. Criar a app Discord

1. Vai a [discord.com/developers/applications](https://discord.com/developers/applications) → **New Application**
2. Em **OAuth2**, copia o **Client ID** e **Client Secret**
3. Adiciona este Redirect URI (o do Supabase, não o teu):
   ```
   https://<o-teu-projeto>.supabase.co/auth/v1/callback
   ```

### 3. Ligar o Discord ao Supabase Auth

Em **Authentication → Providers → Discord** no Supabase:
- Ativa o provider
- Cola o Client ID e Client Secret do passo anterior

### 4. Variáveis de ambiente

```bash
cp .env.example .env
```

Preenche:
- `VITE_SUPABASE_URL` / `VITE_SUPABASE_ANON_KEY` — em Supabase → Project Settings → API
- `SUPABASE_SERVICE_ROLE_KEY` — a mesma página (⚠️ nunca vai para o frontend nem para o Git)
- `SITE_URL` — `http://localhost:5173` em dev
- `VITE_PUBLIC_WORKSPACE_SLUG` — opcional; qual workspace mostrar no site público (usa o
  primeiro criado se não definires)
- `VITE_DISCORD_CLIENT_ID` — Client ID da app Discord, para gerar o link de convite do bot
- `RESEND_API_KEY` / `SUPPORT_TO_EMAIL` / `SUPPORT_FROM_EMAIL` — opcionais, para o
  formulário de `/suporte` enviar email (sem isto, o pedido fica só guardado na BD)

Depois de teres o teu primeiro workspace criado (via onboarding), define
`slug`, `theme_bg`, `theme_accent`, `theme_text`, etc. em **Definições → Identidade visual**
no dashboard, ou diretamente na tabela `workspaces` no Supabase.

### 5. Correr localmente

```bash
npm install
npm run dev
```

As Netlify Functions só correm com o `netlify dev` (não com `vite` sozinho):

```bash
npm install -g netlify-cli   # se ainda não tiveres
netlify dev
```

### 6. Deploy (Netlify)

- Build command: `npm run build`
- Publish dir: `dist`
- Adiciona as env vars do `.env.example` nas **Site settings → Environment variables** do Netlify
- Atualiza o Site URL / Redirect URLs no Supabase e o Redirect URI no Discord para o domínio de produção
