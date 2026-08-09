import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../../lib/supabaseClient'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import { ShopProducts } from '../../components/public/ShopProducts'
import { hasFeature } from '../../lib/plans'
import styles from './Home.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('pt-PT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

export function Home() {
  const { workspace } = usePublicWorkspace()
  const [memberCount, setMemberCount] = useState(null)
  const [posts, setPosts] = useState([])
  const [events, setEvents] = useState([])
  const [partners, setPartners] = useState([])

  useEffect(() => {
    if (!workspace) return
    let cancelled = false

    async function load() {
      const partnersEnabled = hasFeature(workspace.plan, 'partners_affiliates')

      const [{ count }, { data: postRows }, { data: eventRows }, partnerResult] = await Promise.all([
        supabase
          .from('workspace_members')
          .select('id', { count: 'exact', head: true })
          .eq('workspace_id', workspace.id)
          .eq('status', 'active'),
        supabase
          .from('posts')
          .select('*')
          .eq('workspace_id', workspace.id)
          .eq('type', 'post')
          .eq('show_on_homepage', true)
          .order('published_at', { ascending: false })
          .limit(3),
        supabase
          .from('events')
          .select('*')
          .eq('workspace_id', workspace.id)
          .gte('starts_at', new Date().toISOString())
          .order('starts_at', { ascending: true })
          .limit(3),
        partnersEnabled
          ? supabase
              .from('posts')
              .select('*')
              .eq('workspace_id', workspace.id)
              .eq('type', 'partner')
              .eq('show_on_homepage', true)
              .order('published_at', { ascending: false })
          : Promise.resolve({ data: [] }),
      ])

      if (cancelled) return
      setMemberCount(count ?? null)
      setPosts(postRows || [])
      setEvents(eventRows || [])
      setPartners(partnerResult.data || [])
    }

    load()
    return () => { cancelled = true }
  }, [workspace])

  return (
    <div>
      {/* ─── Hero ─── */}
      <section className={styles.hero}>
        <video className={styles.heroVideo} src="/videos/hero-bg.mp4" autoPlay loop muted playsInline />
        <div className={styles.heroOverlay} />
        <div className={styles.heroContent}>
          <span className={styles.heroBadge}>{workspace?.name || 'Hearth'}</span>
          <h1 className={styles.heroTitle}>
            {workspace?.tagline || 'A tua comunidade Discord, gerida como um produto a sério.'}
          </h1>
          <p className={styles.heroSub}>
            Fica a par de tudo o que acontece por aqui — eventos, novidades e a nossa equipa.
          </p>
          <div className={styles.heroActions}>
            {workspace?.discord_invite_url && (
              <a className="btn-primary" href={workspace.discord_invite_url} target="_blank" rel="noreferrer">
                Entrar no Discord
              </a>
            )}
            <Link className="btn-secondary" to="/regras">Ver regras</Link>
          </div>
        </div>
      </section>

      {/* ─── Prova social ─── */}
      <section className={styles.proof}>
        <div className={styles.proofInner}>
          <div className={styles.proofStat}>
            <strong>{memberCount != null ? `${memberCount}+` : '—'}</strong>
            <span>Membros ativos</span>
          </div>
          <div className={styles.proofStat}>
            <strong>24/7</strong>
            <span>Moderação e suporte</span>
          </div>
          <div className={styles.proofStat}>
            <strong>{events.length > 0 ? events.length : '—'}</strong>
            <span>Eventos a acontecer</span>
          </div>
        </div>
      </section>

      {/* ─── Novidades ─── */}
      {posts.length > 0 && (
        <section className={styles.section}>
          <span className="page-label">Novidades</span>
          <h2 className={styles.sectionTitle}>O que se passa na comunidade</h2>
          <div className={styles.postGrid}>
            {posts.map((p) => (
              <div key={p.id} className={styles.postCard}>
                {p.image_url && <img src={p.image_url} alt="" className={styles.postImg} />}
                <h3>{p.title}</h3>
                {p.body && <p>{p.body}</p>}
              </div>
            ))}
          </div>
        </section>
      )}

      {/* ─── Eventos dinâmicos ─── */}
      <section className={styles.section}>
        <span className="page-label">Eventos</span>
        <h2 className={styles.sectionTitle}>Próximos eventos</h2>
        {events.length === 0 ? (
          <p className="page-sub">Ainda não há eventos marcados. Volta em breve.</p>
        ) : (
          <div className={styles.eventGrid}>
            {events.map((ev) => (
              <div key={ev.id} className={styles.eventCard}>
                <span className={styles.eventDate}>{formatDate(ev.starts_at)}</span>
                <h3>{ev.title}</h3>
                {ev.description && <p>{ev.description}</p>}
                {ev.location && <span className={styles.eventLocation}>📍 {ev.location}</span>}
              </div>
            ))}
          </div>
        )}
        <Link to="/eventos" className={styles.viewAll}>Ver calendário completo →</Link>
      </section>

      {/* ─── Sobre nós ─── */}
      <section className={styles.section}>
        <span className="page-label">Sobre nós</span>
        <h2 className={styles.sectionTitle}>Uma comunidade feita para durar</h2>
        <p className={`page-sub ${styles.aboutText}`}>
          {workspace?.about_text ||
            'Configura esta secção nas Definições do dashboard para contar a história da tua comunidade.'}
        </p>
      </section>

      {/* ─── Parceiros ─── */}
      {partners.length > 0 && (
        <section className={styles.section}>
          <span className="page-label">Parceiros</span>
          <h2 className={styles.sectionTitle}>Quem torna isto possível</h2>
          <div className={styles.partnerRow}>
            {partners.map((p) => (
              <a
                key={p.id}
                href={p.link_url || '#'}
                target="_blank"
                rel="noreferrer"
                className={styles.partnerLogo}
              >
                {p.image_url ? <img src={p.image_url} alt={p.title} /> : p.title}
              </a>
            ))}
          </div>
        </section>
      )}

      {/* ─── Loja ─── */}
      <section className={styles.section}>
        <span className="page-label">Loja</span>
        <h2 className={styles.sectionTitle}>Merch oficial</h2>
        <ShopProducts />
      </section>

      {/* ─── CTA final ─── */}
      <section className={styles.cta}>
        <h2>Pronto para fazeres parte?</h2>
        <p>Junta-te à comunidade e acompanha tudo através do teu perfil.</p>
        <div className={styles.heroActions}>
          {workspace?.discord_invite_url && (
            <a className="btn-primary" href={workspace.discord_invite_url} target="_blank" rel="noreferrer">
              Entrar no Discord
            </a>
          )}
          <Link className="btn-secondary" to="/suporte">Falar connosco</Link>
        </div>
      </section>
    </div>
  )
}
