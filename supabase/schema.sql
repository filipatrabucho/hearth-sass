-- ═══════════════════════════════════════════════════════
-- HEARTH — schema inicial (Auth + Dashboard esqueleto)
-- Corre isto no SQL Editor do teu projeto Supabase.
-- ═══════════════════════════════════════════════════════

-- ─── PROFILES ───
-- 1 linha por utilizador Supabase (auth.users), preenchida
-- automaticamente com os dados do Discord via trigger abaixo.
create table if not exists public.profiles (
  id            uuid primary key references auth.users(id) on delete cascade,
  email         text,
  discord_id    text,
  discord_username text,
  discord_avatar   text,
  created_at    timestamptz not null default now()
);

alter table public.profiles enable row level security;

create policy "profiles: user vê o próprio perfil"
  on public.profiles for select
  using (auth.uid() = id);

create policy "profiles: user atualiza o próprio perfil"
  on public.profiles for update
  using (auth.uid() = id);

-- ─── WORKSPACES ───
-- 1 por servidor Discord / comunidade.
create table if not exists public.workspaces (
  id               uuid primary key default gen_random_uuid(),
  name             text not null,
  discord_guild_id text unique,
  plan             text not null default 'free', -- 'free' | 'pro' | 'growth'
  owner_id         uuid not null references public.profiles(id),
  created_at       timestamptz not null default now()
);

alter table public.workspaces enable row level security;

-- ─── WORKSPACE MEMBERS ───
-- Liga profile ↔ workspace. Suporta convite por email:
-- quando ainda não há profile_id (pessoa nunca fez login),
-- fica só o email guardado com status='invited'.
create table if not exists public.workspace_members (
  id           uuid primary key default gen_random_uuid(),
  workspace_id uuid not null references public.workspaces(id) on delete cascade,
  profile_id   uuid references public.profiles(id) on delete cascade,
  email        text not null,
  role         text not null default 'staff', -- 'owner' | 'admin' | 'staff'
  status       text not null default 'invited', -- 'invited' | 'active'
  invited_at   timestamptz not null default now(),
  joined_at    timestamptz,
  unique (workspace_id, email)
);

alter table public.workspace_members enable row level security;

-- ─── RLS: só vês workspaces onde és membro ativo ───
create policy "workspaces: membros veem o workspace"
  on public.workspaces for select
  using (
    exists (
      select 1 from public.workspace_members wm
      where wm.workspace_id = workspaces.id
        and wm.profile_id = auth.uid()
        and wm.status = 'active'
    )
  );

create policy "workspaces: só o owner cria"
  on public.workspaces for insert
  with check (owner_id = auth.uid());

create policy "workspace_members: membros ativos veem a lista"
  on public.workspace_members for select
  using (
    exists (
      select 1 from public.workspace_members wm
      where wm.workspace_id = workspace_members.workspace_id
        and wm.profile_id = auth.uid()
        and wm.status = 'active'
    )
  );

-- (inserts/updates de workspace_members ficam reservados às Netlify
--  Functions, que usam a service role key e passam por trás da RLS)

-- ─── TRIGGER: cria o profile automaticamente no primeiro login ───
create or replace function public.handle_new_user()
returns trigger as $$
begin
  insert into public.profiles (id, email, discord_id, discord_username, discord_avatar)
  values (
    new.id,
    new.email,
    new.raw_user_meta_data ->> 'provider_id',
    coalesce(new.raw_user_meta_data ->> 'full_name', new.raw_user_meta_data ->> 'name'),
    new.raw_user_meta_data ->> 'avatar_url'
  )
  on conflict (id) do nothing;

  -- Se havia um convite pendente para este email, ativa-o.
  update public.workspace_members
  set profile_id = new.id, status = 'active', joined_at = now()
  where email = new.email and status = 'invited';

  return new;
end;
$$ language plpgsql security definer;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute procedure public.handle_new_user();


-- ═══════════════════════════════════════════════════════
-- FIX: recursão infinita nas policies de RLS
-- Corre isto no SQL Editor do Supabase (depois do schema.sql original)
-- ═══════════════════════════════════════════════════════

-- Função auxiliar: corre com privilégios elevados (security definer),
-- por isso NÃO aciona a RLS de "workspace_members" outra vez ao verificar
-- a condição — é isto que evita a recursão infinita.
create or replace function public.is_workspace_member(_workspace_id uuid)
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
  );
$$;

-- ─── Substitui as policies que causavam a recursão ───

drop policy if exists "workspace_members: membros ativos veem a lista" on public.workspace_members;
create policy "workspace_members: membros ativos veem a lista"
  on public.workspace_members for select
  using ( public.is_workspace_member(workspace_id) );

drop policy if exists "workspaces: membros veem o workspace" on public.workspaces;
create policy "workspaces: membros veem o workspace"
  on public.workspaces for select
  using ( public.is_workspace_member(id) );