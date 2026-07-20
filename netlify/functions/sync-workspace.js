import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.VITE_SUPABASE_URL
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY

const supabaseAdmin = createClient(supabaseUrl, serviceKey)

// STUB: enquanto o bot Discord (discord.js, Fly.io) não existir, isto só
// marca last_synced_at. Quando o bot estiver pronto, este endpoint (ou um
// equivalente) deve pedir ao bot a lista real de membros/canais/cargos do
// servidor e populá-la nas tabelas correspondentes.
export async function handler(event) {
  if (event.httpMethod !== 'POST') {
    return { statusCode: 405, body: 'Method Not Allowed' }
  }

  try {
    const authHeader = event.headers.authorization || ''
    const token = authHeader.replace('Bearer ', '')
    if (!token) return { statusCode: 401, body: JSON.stringify({ error: 'Sem sessão.' }) }

    const { data: userData, error: userError } = await supabaseAdmin.auth.getUser(token)
    if (userError || !userData?.user) {
      return { statusCode: 401, body: JSON.stringify({ error: 'Sessão inválida.' }) }
    }

    const { workspaceId } = JSON.parse(event.body || '{}')
    if (!workspaceId) {
      return { statusCode: 400, body: JSON.stringify({ error: 'workspaceId é obrigatório.' }) }
    }

    const { data: membership } = await supabaseAdmin
      .from('workspace_members')
      .select('role')
      .eq('workspace_id', workspaceId)
      .eq('profile_id', userData.user.id)
      .eq('status', 'active')
      .maybeSingle()

    if (!membership) {
      return { statusCode: 403, body: JSON.stringify({ error: 'Sem acesso a este workspace.' }) }
    }

    const { error: updateError } = await supabaseAdmin
      .from('workspaces')
      .update({ last_synced_at: new Date().toISOString() })
      .eq('id', workspaceId)

    if (updateError) throw updateError

    return {
      statusCode: 200,
      body: JSON.stringify({
        ok: true,
        note: 'Sincronização simulada — liga o bot Discord (serviço separado) para dados reais.',
      }),
    }
  } catch (err) {
    console.error('sync-workspace error:', err)
    return { statusCode: 500, body: JSON.stringify({ error: 'Erro ao sincronizar.' }) }
  }
}
