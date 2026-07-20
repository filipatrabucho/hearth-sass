import { usePublicWorkspace } from '../../contexts/PublicWorkspaceContext'
import styles from './ShopProducts.module.css'

// Catálogo Fourthwall — widget configurável por workspace (Hearth Merch,
// add-on independente do plano). Sem loja ligada ainda, mostra placeholder.
export function ShopProducts() {
  const { workspace } = usePublicWorkspace()
  const storeUrl = workspace?.fourthwall_store_url

  if (!storeUrl) {
    return (
      <div className={styles.empty}>
        <span aria-hidden="true" className={styles.emptyIcon}>🛍️</span>
        <p>A loja oficial ainda não está ligada.</p>
        <p className={styles.emptyHint}>Configura o Fourthwall em Definições → Loja para ativar este espaço.</p>
      </div>
    )
  }

  return (
    <div className={styles.frameWrap}>
      <iframe
        src={storeUrl}
        title="Loja"
        className={styles.frame}
        loading="lazy"
      />
    </div>
  )
}
