import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.VITE_SUPABASE_URL
const serviceKey = process.env.SUPABASE_SERVICE_ROLE_KEY
const resendApiKey = process.env.RESEND_API_KEY
const supportToEmail = process.env.SUPPORT_TO_EMAIL
const supportFromEmail = process.env.SUPPORT_FROM_EMAIL || 'onboarding@resend.dev'

const supabaseAdmin = createClient(supabaseUrl, serviceKey)

export async function handler(event) {
  if (event.httpMethod !== 'POST') {
    return { statusCode: 405, body: 'Method Not Allowed' }
  }

  try {
    const { workspaceId, name, email, subject, message } = JSON.parse(event.body || '{}')
    if (!workspaceId || !name || !email || !message) {
      return { statusCode: 400, body: JSON.stringify({ error: 'Campos obrigatórios em falta.' }) }
    }

    const { error: insertError } = await supabaseAdmin
      .from('support_requests')
      .insert({ workspace_id: workspaceId, name, email, subject: subject || null, message })

    if (insertError) throw insertError

    // O email é "nice to have" — se o Resend não estiver configurado, o
    // pedido já ficou guardado na BD e visível no dashboard de tickets.
    if (resendApiKey && supportToEmail) {
      await fetch('https://api.resend.com/emails', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${resendApiKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          from: supportFromEmail,
          to: supportToEmail,
          reply_to: email,
          subject: `[Suporte] ${subject || 'Novo pedido de contacto'}`,
          text: `De: ${name} <${email}>\n\n${message}`,
        }),
      }).catch((err) => console.error('contact-support: falha ao enviar email via Resend:', err))
    }

    return { statusCode: 200, body: JSON.stringify({ ok: true }) }
  } catch (err) {
    console.error('contact-support error:', err)
    return { statusCode: 500, body: JSON.stringify({ error: 'Erro ao enviar o pedido.' }) }
  }
}
