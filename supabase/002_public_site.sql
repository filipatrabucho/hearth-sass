-- ═══════════════════════════════════════════════════════
-- HEARTH — Fase 0: site público + identidade visual + Fase 1: welcome flow
-- Corre isto no SQL Editor do Supabase DEPOIS de supabase/schema.sql
-- ═══════════════════════════════════════════════════════

-- ─── Helper: é owner/admin deste workspace? (para policies de escrita) ───
create or replace function public.is_workspace_admin(_workspace_id uuid)
returns boolean
language sql
security definer
set search_path = public
stable
as $$
  select exists (
    select 1
    from public.workspace_members
    where workspace_id = _workspace_id
      and profile_id = auth.uid()
      and status = 'active'
      and role in ('owner', 'admin')
  );
$$;

-- ─── WORKSPACES: identidade visual + slug público ───
alter table public.workspaces
  add column if not exists slug             text unique,
  add column if not exists theme_bg         text not null default '#080B0F',
  add column if not exists theme_accent     text not null default '#7B61FF',
  add column if not exists theme_text       text not null default '#F5F3EF',
  add column if not exists logo_url         text,
  add column if not exists banner_url       text,
  add column if not exists discord_invite_url text,
  add column if not exists social_x_url        text,
  add column if not exists social_instagram_url text,
  add column if not exists social_youtube_url  text,
  add column if not exists tagline          text,
  add column if not exists about_text       text,
  add column if not exists fourthwall_store_url text,
  add column if not exists last_synced_at    timestamptz;

-- slug obrigatório a partir de agora, mas as linhas existentes podem não ter —
-- preenche com o id se estiver vazio para não partir o unique constraint.
update public.workspaces set slug = id::text where slug is null;
alter table public.workspaces alter column slug set not null;

-- Leitura pública: o site público precisa de ler nome/tema/logo por slug,
-- sem sessão. Não há nada sensível nestas colunas (owner_id não é segredo).
drop policy if exists "workspaces: leitura pública" on public.workspaces;
create policy "workspaces: leitura pública"
  on public.workspaces for select
  using (true);

-- Atualização: só owner/admin editam a identidade do próprio workspace.
drop policy if exists "workspaces: owner/admin atualizam" on public.workspaces;
create policy "workspaces: owner/admin atualizam"
  on public.workspaces for update
  using (public.is_workspace_admin(id));

-- ─── WORKSPACE_MEMBERS: perfil público de staff (para /equipa) ───
alter table public.workspace_members
  add column if not exists public_name      text,
  add column if not exists public_title     text,
  add column if not exists public_avatar_url text,
  add column if not exists bio              text,
  add column if not exists show_on_team_page boolean not null default false,
  add column if not exists invites_count    integer not null default 0;

-- View com só os campos seguros para mostrar publicamente em /equipa.
create or replace view public.team_page_members as
  select
    wm.workspace_id,
    wm.role,
    coalesce(wm.public_name, p.discord_username) as display_name,
    wm.public_title,
    coalesce(wm.public_avatar_url, p.discord_avatar) as avatar_url,
    wm.bio,
    wm.joined_at
  from public.workspace_members wm
  left join public.profiles p on p.id = wm.profile_id
  where wm.status = 'active' and wm.show_on_team_page = true;

grant select on public.team_page_members to anon, authenticated;

-- Owner/admin também podem atualizar os campos públicos de qualquer membro
-- do seu workspace (ex: definir o cargo/bio que aparece em /equipa).
drop policy if exists "workspace_members: owner/admin atualizam" on public.workspace_members;
create policy "workspace_members: owner/admin atualizam"
  on public.workspace_members for update
  using (public.is_workspace_admin(workspace_id));

-- ─── POSTS (features/novidades) e PARCEIROS ───
create table if not exists public.posts (
  id                uuid primary key default gen_random_uuid(),
  workspace_id      uuid not null references public.workspaces(id) on delete cascade,
  type              text not null default 'post', -- 'post' | 'partner'
  title             text not null,
  body              text,
  image_url         text,
  link_url          text,
  show_on_homepage  boolean not null default false,
  published_at      timestamptz not null default now(),
  created_by        uuid references public.profiles(id),
  created_at        timestamptz not null default now()
);

alter table public.posts enable row level security;

drop policy if exists "posts: leitura pública" on public.posts;
create policy "posts: leitura pública"
  on public.posts for select
  using (true);

drop policy if exists "posts: staff gere" on public.posts;
create policy "posts: staff gere"
  on public.posts for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));

-- ─── CHANGELOG PÚBLICO (/updates) ───
create table if not exists public.changelog_entries (
  id            uuid primary key default gen_random_uuid(),
  workspace_id  uuid not null references public.workspaces(id) on delete cascade,
  title         text not null,
  body          text not null,
  published_at  timestamptz not null default now(),
  created_by    uuid references public.profiles(id),
  created_at    timestamptz not null default now()
);

alter table public.changelog_entries enable row level security;

drop policy if exists "changelog: leitura pública" on public.changelog_entries;
create policy "changelog: leitura pública"
  on public.changelog_entries for select
  using (true);

drop policy if exists "changelog: staff gere" on public.changelog_entries;
create policy "changelog: staff gere"
  on public.changelog_entries for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));

-- ─── EVENTOS (dinâmicos na homepage, CRUD no dashboard) ───
create table if not exists public.events (
  id            uuid primary key default gen_random_uuid(),
  workspace_id  uuid not null references public.workspaces(id) on delete cascade,
  title         text not null,
  description   text,
  image_url     text,
  location      text,
  starts_at     timestamptz not null,
  ends_at       timestamptz,
  discord_message_id text,
  created_by    uuid references public.profiles(id),
  created_at    timestamptz not null default now()
);

alter table public.events enable row level security;

drop policy if exists "events: leitura pública" on public.events;
create policy "events: leitura pública"
  on public.events for select
  using (true);

drop policy if exists "events: staff gere" on public.events;
create policy "events: staff gere"
  on public.events for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));

-- ─── SUPORTE (/suporte) ───
-- Escrita só pela Netlify Function (service role, passa por trás da RLS).
create table if not exists public.support_requests (
  id            uuid primary key default gen_random_uuid(),
  workspace_id  uuid not null references public.workspaces(id) on delete cascade,
  name          text not null,
  email         text not null,
  subject       text,
  message       text not null,
  status        text not null default 'open', -- 'open' | 'closed'
  created_at    timestamptz not null default now()
);

alter table public.support_requests enable row level security;

drop policy if exists "support_requests: staff vê" on public.support_requests;
create policy "support_requests: staff vê"
  on public.support_requests for select
  using (public.is_workspace_member(workspace_id));

drop policy if exists "support_requests: staff atualiza" on public.support_requests;
create policy "support_requests: staff atualiza"
  on public.support_requests for update
  using (public.is_workspace_member(workspace_id));

-- ─── WELCOME FLOW (configurável, entregue pelo bot Discord) ───
create table if not exists public.welcome_flow_configs (
  workspace_id        uuid primary key references public.workspaces(id) on delete cascade,
  enabled             boolean not null default false,
  message_text        text,
  embed_title         text,
  embed_description   text,
  embed_color         text default '#7B61FF',
  auto_role_id        text,
  dm_enabled          boolean not null default false,
  dm_message          text,
  verification_enabled boolean not null default false,
  verification_type   text default 'button', -- 'button' | 'reaction'
  updated_at          timestamptz not null default now()
);

alter table public.welcome_flow_configs enable row level security;

drop policy if exists "welcome_flow: staff vê" on public.welcome_flow_configs;
create policy "welcome_flow: staff vê"
  on public.welcome_flow_configs for select
  using (public.is_workspace_member(workspace_id));

drop policy if exists "welcome_flow: owner/admin gere" on public.welcome_flow_configs;
create policy "welcome_flow: owner/admin gere"
  on public.welcome_flow_configs for all
  using (public.is_workspace_admin(workspace_id))
  with check (public.is_workspace_admin(workspace_id));
