import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.VITE_SUPABASE_URL
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY
const siteUrl = process.env.SITE_URL || 'http://localhost:5173'

const supabaseAdmin = createClient(supabaseUrl, serviceKey)

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
    const callerId = userData.user.id

    const { workspaceId, email, role } = JSON.parse(event.body || '{}')
    if (!workspaceId || !email) {
      return { statusCode: 400, body: JSON.stringify({ error: 'workspaceId e email são obrigatórios.' }) }
    }
    const finalRole = ['admin', 'staff'].includes(role) ? role : 'staff'

    // Confirma que quem está a convidar é owner/admin deste workspace
    const { data: callerMembership, error: membershipError } = await supabaseAdmin
      .from('workspace_members')
      .select('role, status')
      .eq('workspace_id', workspaceId)
      .eq('profile_id', callerId)
      .eq('status', 'active')
      .maybeSingle()

    if (membershipError) throw membershipError
    if (!callerMembership || !['owner', 'admin'].includes(callerMembership.role)) {
      return { statusCode: 403, body: JSON.stringify({ error: 'Sem permissão para convidar staff neste workspace.' }) }
    }

    // Cria o registo pendente (fica 'invited' até a pessoa aceitar)
    const { error: insertError } = await supabaseAdmin
      .from('workspace_members')
      .upsert(
        { workspace_id: workspaceId, email, role: finalRole, status: 'invited' },
        { onConflict: 'workspace_id,email', ignoreDuplicates: false }
      )

    if (insertError) throw insertError

    // Envia o email de convite (magic link) — o Supabase trata do resto.
    // Se o email já tiver conta, isto reenvia um link de login normal.
    const { error: inviteError } = await supabaseAdmin.auth.admin.inviteUserByEmail(email, {
      redirectTo: `${siteUrl}/auth/callback`,
    })

    // Já existir conta não é um erro fatal para o nosso fluxo —
    // o trigger liga o convite assim que a pessoa entrar.
    if (inviteError && !String(inviteError.message).toLowerCase().includes('already been registered')) {
      throw inviteError
    }

    return { statusCode: 200, body: JSON.stringify({ ok: true }) }
  } catch (err) {
    console.error('invite-staff error:', err)
    return { statusCode: 500, body: JSON.stringify({ error: 'Erro ao convidar staff.' }) }
  }
}
