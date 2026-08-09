-- ═══════════════════════════════════════════════════════
-- HEARTH — Gestão de membros, bans e logs (plano Free)
-- Corre isto no SQL Editor do Supabase DEPOIS de 002_public_site.sql
--
-- IMPORTANTE: community_members é o roster da comunidade Discord (os
-- membros regulares do servidor) — diferente de workspace_members, que é
-- só a equipa com acesso ao dashboard. Sem o bot Discord ligado, esta
-- tabela fica vazia/manual; quando o bot existir, ele passa a sincronizá-la.
-- ═══════════════════════════════════════════════════════

create table if not exists public.community_members (
  id                uuid primary key default gen_random_uuid(),
  workspace_id      uuid not null references public.workspaces(id) on delete cascade,
  profile_id        uuid references public.profiles(id) on delete set null,
  discord_user_id   text,
  username          text not null,
  avatar_url        text,
  status            text not null default 'active', -- 'active' | 'kicked' | 'banned' | 'left'
  invites_count     integer not null default 0,
  joined_discord_at timestamptz not null default now(),
  created_at        timestamptz not null default now()
);

alter table public.community_members enable row level security;

drop policy if exists "community_members: staff gere" on public.community_members;
create policy "community_members: staff gere"
  on public.community_members for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));

-- Para a barra de progresso pessoal em /regras, o próprio membro
-- (autenticado, mesmo sem ser staff) pode ler a sua própria linha.
drop policy if exists "community_members: o próprio vê-se a si" on public.community_members;
create policy "community_members: o próprio vê-se a si"
  on public.community_members for select
  using (profile_id = auth.uid());

create table if not exists public.member_bans (
  id            uuid primary key default gen_random_uuid(),
  workspace_id  uuid not null references public.workspaces(id) on delete cascade,
  member_id     uuid references public.community_members(id) on delete set null,
  member_label  text not null,
  reason        text,
  banned_by     uuid references public.profiles(id),
  banned_at     timestamptz not null default now(),
  active        boolean not null default true,
  unbanned_at   timestamptz,
  unbanned_by   uuid references public.profiles(id)
);

alter table public.member_bans enable row level security;

drop policy if exists "member_bans: staff gere" on public.member_bans;
create policy "member_bans: staff gere"
  on public.member_bans for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));

create table if not exists public.moderation_logs (
  id            uuid primary key default gen_random_uuid(),
  workspace_id  uuid not null references public.workspaces(id) on delete cascade,
  member_id     uuid references public.community_members(id) on delete set null,
  member_label  text not null,
  action        text not null, -- 'warn' | 'kick' | 'ban' | 'unban' | 'timeout' | 'note'
  reason        text,
  created_by    uuid references public.profiles(id),
  created_at    timestamptz not null default now()
);

alter table public.moderation_logs enable row level security;

drop policy if exists "moderation_logs: staff gere" on public.moderation_logs;
create policy "moderation_logs: staff gere"
  on public.moderation_logs for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));
