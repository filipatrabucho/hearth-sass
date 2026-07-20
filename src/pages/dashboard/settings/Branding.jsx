import { useEffect, useState } from 'react'
import { supabase } from '../../../lib/supabaseClient'
import { useAuth } from '../../../contexts/AuthContext'
import { applyWorkspaceTheme } from '../../../lib/theme'
import styles from './Branding.module.css'

const FIELDS = [
  { key: 'name', label: 'Nome do workspace', type: 'text' },
  { key: 'slug', label: 'Slug (usado no URL público)', type: 'text' },
  { key: 'tagline', label: 'Frase de destaque (hero)', type: 'text' },
  { key: 'about_text', label: 'Texto da secção "Sobre nós"', type: 'text' },
  { key: 'discord_invite_url', label: 'Link de convite do Discord', type: 'url' },
  { key: 'fourthwall_store_url', label: 'URL da loja Fourthwall (embed)', type: 'url' },
  { key: 'social_x_url', label: 'X / Twitter', type: 'url' },
  { key: 'social_instagram_url', label: 'Instagram', type: 'url' },
  { key: 'social_youtube_url', label: 'YouTube', type: 'url' },
  { key: 'logo_url', label: 'URL do logo', type: 'url' },
  { key: 'banner_url', label: 'URL do banner', type: 'url' },
]

export function Branding() {
  const { currentWorkspace, refreshWorkspaces } = useAuth()
  const [form, setForm] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState(null)

  useEffect(() => {
    if (!currentWorkspace) return
    setForm({
      name: currentWorkspace.name || '',
      slug: currentWorkspace.slug || '',
      tagline: currentWorkspace.tagline || '',
      about_text: currentWorkspace.about_text || '',
      discord_invite_url: currentWorkspace.discord_invite_url || '',
      fourthwall_store_url: currentWorkspace.fourthwall_store_url || '',
      social_x_url: currentWorkspace.social_x_url || '',
      social_instagram_url: currentWorkspace.social_instagram_url || '',
      social_youtube_url: currentWorkspace.social_youtube_url || '',
      logo_url: currentWorkspace.logo_url || '',
      banner_url: currentWorkspace.banner_url || '',
      theme_bg: currentWorkspace.theme_bg || '#080B0F',
      theme_accent: currentWorkspace.theme_accent || '#7B61FF',
      theme_text: currentWorkspace.theme_text || '#F5F3EF',
    })
  }, [currentWorkspace])

  function update(key, value) {
    const next = { ...form, [key]: value }
    setForm(next)
    if (key.startsWith('theme_')) applyWorkspaceTheme(next)
  }

  async function handleSubmit(e) {
    e.preventDefault()
    if (!currentWorkspace) return
    setSubmitting(true)
    setFeedback(null)
    try {
      const { error } = await supabase
        .from('workspaces')
        .update({ ...form, slug: form.slug.trim().toLowerCase().replace(/\s+/g, '-') })
        .eq('id', currentWorkspace.id)
      if (error) throw error
      await refreshWorkspaces()
      setFeedback({ type: 'ok', text: 'Identidade visual guardada.' })
    } catch (err) {
      setFeedback({ type: 'err', text: err.message || 'Não foi possível guardar.' })
    } finally {
      setSubmitting(false)
    }
  }

  if (!form) return null

  return (
    <div>
      <h1 className="page-title">Identidade visual</h1>
      <p className="page-sub">
        Cada workspace configura as suas próprias cores — o Hearth adapta-se, sem impor uma palette fixa.
      </p>

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.colorRow}>
          <div className={styles.colorField}>
            <label className={styles.label}>Fundo</label>
            <input type="color" value={form.theme_bg} onChange={(e) => update('theme_bg', e.target.value)} />
          </div>
          <div className={styles.colorField}>
            <label className={styles.label}>Destaque</label>
            <input type="color" value={form.theme_accent} onChange={(e) => update('theme_accent', e.target.value)} />
          </div>
          <div className={styles.colorField}>
            <label className={styles.label}>Texto</label>
            <input type="color" value={form.theme_text} onChange={(e) => update('theme_text', e.target.value)} />
          </div>
        </div>
        <p className={styles.previewHint}>A pré-visualização aplica-se já a todo o dashboard e ao site público.</p>

        <div className={styles.grid}>
          {FIELDS.map((f) => (
            <div className={styles.field} key={f.key}>
              <label className={styles.label} htmlFor={f.key}>{f.label}</label>
              <input
                id={f.key}
                type={f.type}
                className={styles.input}
                value={form[f.key]}
                onChange={(e) => update(f.key, e.target.value)}
              />
            </div>
          ))}
        </div>

        <button type="submit" className="btn-primary" disabled={submitting}>
          {submitting ? 'A guardar…' : 'Guardar alterações'}
        </button>

        {feedback && (
          <p className={feedback.type === 'ok' ? styles.ok : styles.err}>{feedback.text}</p>
        )}
      </form>
    </div>
  )
}
