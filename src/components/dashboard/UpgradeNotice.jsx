import styles from './UpgradeNotice.module.css'

export function UpgradeNotice({ icon, title, sub, requiredPlanLabel }) {
  return (
    <div>
      <div className={styles.badge}>
        <span aria-hidden="true">🔒</span> Disponível no plano {requiredPlanLabel}
      </div>
      <h1 className="page-title">{icon} {title}</h1>
      <p className="page-sub">{sub}</p>
      <div className={styles.placeholder}>
        Faz upgrade do workspace para desbloquear esta funcionalidade.
      </div>
    </div>
  )
}
