# Hearth — Dashboard (esqueleto: Auth + estrutura base)

React 18 + Vite + React Router + Supabase (Auth/DB) + Netlify Functions.

## O que já está feito

- Login com Discord (via Supabase Auth OAuth2)
- Perfil criado automaticamente no primeiro login (trigger na BD)
- Onboarding: criar o primeiro workspace
- Convite de staff por email (usa o invite nativo do Supabase — não precisas de servidor de email)
- Layout do dashboard: sidebar + topbar + workspace switcher
- Páginas placeholder para Analytics, Membros, Moderação, Eventos, Tickets, XP & Níveis
- Página de Equipa (Definições) já funcional: convida e lista staff

## O que falta (próximos passos)

- Ligar o bot Discord de verdade (hoje o `discord_guild_id` é só um campo manual no onboarding)
- Implementar cada secção do dashboard com dados reais
- Reenvio de magic link para staff que perdeu a sessão (login "sem Discord")
- Stripe (o site de marketing já tem o placeholder pronto para isto)

---

## Setup

### 1. Criar o projeto Supabase

1. Cria um projeto em [supabase.com](https://supabase.com)
2. Vai a **SQL Editor** e corre o conteúdo de `supabase/schema.sql`
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
- Adiciona as env vars (as 4 do `.env.example`) nas **Site settings → Environment variables** do Netlify
- Atualiza o Site URL / Redirect URLs no Supabase e o Redirect URI no Discord para o domínio de produção
