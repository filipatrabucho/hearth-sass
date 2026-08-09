import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.VITE_SUPABASE_URL
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY
const resendApiKey = process.env.RESEND_API_KEY
const supportFromEmail = process.env.SUPPORT_FROM_EMAIL || 'onboarding@resend.dev'

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

    const { ticketId, body } = JSON.parse(event.body || '{}')
    if (!ticketId || !body?.trim()) {
      return { statusCode: 400, body: JSON.stringify({ error: 'ticketId e body são obrigatórios.' }) }
    }

    const { data: ticket, error: ticketError } = await supabaseAdmin
      .from('support_requests')
      .select('*')
      .eq('id', ticketId)
      .maybeSingle()
    if (ticketError || !ticket) {
      return { statusCode: 404, body: JSON.stringify({ error: 'Ticket não encontrado.' }) }
    }

    const { data: membership } = await supabaseAdmin
      .from('workspace_members')
      .select('role')
      .eq('workspace_id', ticket.workspace_id)
      .eq('profile_id', userData.user.id)
      .eq('status', 'active')
      .maybeSingle()
    if (!membership) {
      return { statusCode: 403, body: JSON.stringify({ error: 'Sem acesso a este workspace.' }) }
    }

    const { error: insertError } = await supabaseAdmin.from('ticket_replies').insert({
      workspace_id: ticket.workspace_id,
      support_request_id: ticketId,
      body: body.trim(),
      created_by: userData.user.id,
    })
    if (insertError) throw insertError

    if (resendApiKey) {
      await fetch('https://api.resend.com/emails', {
        method: 'POST',
        headers: { Authorization: `Bearer ${resendApiKey}`, 'Content-Type': 'application/json' },
        body: JSON.stringify({
          from: supportFromEmail,
          to: ticket.email,
          subject: `Re: ${ticket.subject || 'O teu pedido de suporte'}`,
          text: body,
        }),
      }).catch((err) => console.error('ticket-reply: falha ao enviar email via Resend:', err))
    }

    return { statusCode: 200, body: JSON.stringify({ ok: true }) }
  } catch (err) {
    console.error('ticket-reply error:', err)
    return { statusCode: 500, body: JSON.stringify({ error: 'Erro ao responder ao ticket.' }) }
  }
}
