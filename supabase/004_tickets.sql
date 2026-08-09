-- ═══════════════════════════════════════════════════════
-- HEARTH — Sistema de tickets (plano Free)
-- Corre isto no SQL Editor do Supabase DEPOIS de 003_members_moderation.sql
--
-- Os tickets são os pedidos submetidos em /suporte (support_requests).
-- Categorias, FAQ automático e SLA (Fase 2, Pro) ficam para depois —
-- isto cobre "Sistema de tickets" tal como está na matriz do plano Free:
-- listar, filtrar, responder, mudar estado e atribuir a mim.
-- ═══════════════════════════════════════════════════════

alter table public.support_requests
  add column if not exists assigned_to uuid references public.profiles(id);
-- status usado: 'open' | 'in_progress' | 'closed'

create table if not exists public.ticket_replies (
  id                 uuid primary key default gen_random_uuid(),
  workspace_id       uuid not null references public.workspaces(id) on delete cascade,
  support_request_id uuid not null references public.support_requests(id) on delete cascade,
  body               text not null,
  created_by         uuid references public.profiles(id),
  created_at         timestamptz not null default now()
);

alter table public.ticket_replies enable row level security;

drop policy if exists "ticket_replies: staff gere" on public.ticket_replies;
create policy "ticket_replies: staff gere"
  on public.ticket_replies for all
  using (public.is_workspace_member(workspace_id))
  with check (public.is_workspace_member(workspace_id));
