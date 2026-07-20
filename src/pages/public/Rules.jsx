import { useAuth } from '../../contexts/AuthContext'
import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import { INVITE_TIERS, getTierForInvites, getNextTier } from '../../lib/inviteTiers'
import styles from './Rules.module.css'

const RULES = [
  { title: 'Respeito acima de tudo', desc: 'Sem discurso de ódio, assédio ou discriminação de qualquer tipo.' },
  { title: 'Conteúdo apropriado', desc: 'Sem NSFW, spam ou conteúdo ilegal em qualquer canal.' },
  { title: 'Sem autopromoção não autorizada', desc: 'Publicidade a servidores ou produtos só com autorização da staff.' },
  { title: 'Segue as decisões da staff', desc: 'Discordâncias resolvem-se em privado, através de um ticket de apelação.' },
  { title: 'Usa os canais certos', desc: 'Mantém as conversas no canal apropriado para cada assunto.' },
]

export function Rules() {
  const { workspace } = usePublicWorkspace()
  const { workspaces } = useAuth()

  const myMembership = workspace ? workspaces.find((w) => w.id === workspace.id) : null
  const myInvites = myMembership?.invites_count ?? null

  return (
    <div className={styles.wrap}>
      <span className="page-label">Comunidade</span>
      <h1 className="page-title">Regras</h1>
      <p className="page-sub">
        Regras simples para mantermos {workspace?.name || 'a comunidade'} um sítio agradável para todos.
      </p>

      <ol className={styles.rulesList}>
        {RULES.map((r, i) => (
          <li key={r.title} className={styles.ruleItem}>
            <span className={styles.ruleNumber}>{String(i + 1).padStart(2, '0')}</span>
            <div>
              <h3>{r.title}</h3>
              <p>{r.desc}</p>
            </div>
          </li>
        ))}
      </ol>

      <div className={styles.tiersSection}>
        <span className="page-label">Progressão de convites</span>
        <h2 className={styles.tiersTitle}>De Bronze a Emerald</h2>
        <p className="page-sub">
          Convida amigos para a comunidade e sobe de tier. Cada tier desbloqueia funções e canais exclusivos.
        </p>

        <div className={styles.tierLadder}>
          {INVITE_TIERS.map((tier) => (
            <div key={tier.key} className={styles.tierCard} style={{ '--tier-color': tier.color }}>
              <span className={styles.tierDot} />
              <strong>{tier.name}</strong>
              <span className={styles.tierMin}>{tier.min}+ convites</span>
            </div>
          ))}
        </div>

        {myInvites != null && (
          <div className={styles.myProgress}>
            {(() => {
              const current = getTierForInvites(myInvites)
              const next = getNextTier(myInvites)
              const pct = next
                ? Math.min(100, Math.round(((myInvites - current.min) / (next.min - current.min)) * 100))
                : 100
              return (
                <>
                  <p className={styles.myProgressLabel}>
                    Tens <strong>{myInvites}</strong> convites — tier atual: <strong style={{ color: current.color }}>{current.name}</strong>
                    {next && ` · faltam ${next.min - myInvites} para ${next.name}`}
                  </p>
                  <div className={styles.progressTrack}>
                    <div className={styles.progressFill} style={{ width: `${pct}%`, background: current.color }} />
                  </div>
                </>
              )
            })()}
          </div>
        )}
      </div>
    </div>
  )
}
