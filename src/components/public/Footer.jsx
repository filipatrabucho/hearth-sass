import { Link } from 'react-router-dom'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import { SocialIcons } from './SocialIcons'
import styles from './Footer.module.css'

export function Footer() {
  const { workspace } = usePublicWorkspace()
  const year = new Date().getFullYear()

  return (
    <footer className={styles.footer}>
      <div className={styles.inner}>
        <div className={styles.brand}>
          <div className={styles.logo}>
            <div className={styles.logoMark} aria-hidden="true">
              <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
                <path d="M8 2L13 6V14H10V10H6V14H3V6L8 2Z" fill="white" />
              </svg>
            </div>
            {workspace?.name || 'Hearth'}
          </div>
          <p className={styles.tagline}>
            {workspace?.tagline || 'Gestão profissional de comunidades Discord.'}
          </p>
          <SocialIcons
            discord={workspace?.discord_invite_url}
            x={workspace?.social_x_url}
            instagram={workspace?.social_instagram_url}
            youtube={workspace?.social_youtube_url}
          />
        </div>

        <div className={styles.col}>
          <span className={styles.colTitle}>Comunidade</span>
          <Link to="/regras">Regras</Link>
          <Link to="/equipa">Equipa</Link>
          <Link to="/eventos">Eventos</Link>
          <Link to="/updates">Updates</Link>
        </div>

        <div className={styles.col}>
          <span className={styles.colTitle}>Ajuda</span>
          <Link to="/suporte">Suporte</Link>
          <Link to="/login">Entrar</Link>
        </div>
      </div>

      <div className={styles.bottom}>
        <span>© {year} {workspace?.name || 'Hearth'}. Todos os direitos reservados.</span>
        <span className={styles.poweredBy}>Powered by Hearth</span>
      </div>
    </footer>
  )
}
