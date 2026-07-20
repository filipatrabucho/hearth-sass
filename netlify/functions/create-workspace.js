import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.VITE_SUPABASE_URL
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY

// Cliente admin — só corre no servidor, nunca no browser.
const supabaseAdmin = createClient(supabaseUrl, serviceKey)

export async function handler(event) {
  if (event.httpMethod !== 'POST') {
    return { statusCode: 405, body: 'Method Not Allowed' }
  }

  try {
    const authHeader = event.headers.authorization || ''
    const token = authHeader.replace('Bearer ', '')
    if (!token) return { statusCode: 401, body: JSON.stringify({ error: 'Sem sessão.' }) }

    // Valida o token do utilizador que está a chamar a function
    const { data: userData, error: userError } = await supabaseAdmin.auth.getUser(token)
    if (userError || !userData?.user) {
      return { statusCode: 401, body: JSON.stringify({ error: 'Sessão inválida.' }) }
    }
    const userId = userData.user.id

    const { name, discordGuildId } = JSON.parse(event.body || '{}')
    if (!name || !name.trim()) {
      return { statusCode: 400, body: JSON.stringify({ error: 'Nome do workspace é obrigatório.' }) }
    }
    const guildId = discordGuildId?.trim() || null

    // Se já existir um workspace com este discord_guild_id (ex: de uma
    // tentativa anterior que falhou a meio), tratamos isto de forma idempotente
    // em vez de rebentar com "duplicate key".
    if (guildId) {
      const { data: existing, error: existingError } = await supabaseAdmin
        .from('workspaces')
        .select('*')
        .eq('discord_guild_id', guildId)
        .maybeSingle()

      if (existingError) throw existingError

      if (existing) {
        if (existing.owner_id !== userId) {
          return {
            statusCode: 409,
            body: JSON.stringify({ error: 'Este servidor Discord já está associado a outro workspace.' }),
          }
        }

        // É teu — garante que ficas como membro ativo e devolve-o (self-heal).
        const { error: fixMemberError } = await supabaseAdmin
          .from('workspace_members')
          .upsert(
            {
              workspace_id: existing.id,
              profile_id: userId,
              email: userData.user.email,
              role: 'owner',
              status: 'active',
              joined_at: new Date().toISOString(),
            },
            { onConflict: 'workspace_id,email' }
          )
        if (fixMemberError) throw fixMemberError

        return { statusCode: 200, body: JSON.stringify({ workspace: existing }) }
      }
    }

    const { data: workspace, error: wsError } = await supabaseAdmin
      .from('workspaces')
      .insert({
        name: name.trim(),
        discord_guild_id: guildId,
        owner_id: userId,
      })
      .select()
      .single()

    if (wsError) throw wsError

    const { error: memberError } = await supabaseAdmin
      .from('workspace_members')
      .insert({
        workspace_id: workspace.id,
        profile_id: userId,
        email: userData.user.email,
        role: 'owner',
        status: 'active',
        joined_at: new Date().toISOString(),
      })

    if (memberError) {
      // Compensating rollback — nunca deixar um workspace "órfão" para trás.
      await supabaseAdmin.from('workspaces').delete().eq('id', workspace.id)
      throw memberError
    }

    return { statusCode: 200, body: JSON.stringify({ workspace }) }
  } catch (err) {
    console.error('create-workspace error:', err)
    return { statusCode: 500, body: JSON.stringify({ error: 'Erro ao criar workspace.' }) }
  }
}