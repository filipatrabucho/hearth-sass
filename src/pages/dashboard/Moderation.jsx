import { UpgradeNotice } from '../../components/dashboard/UpgradeNotice'
import { minPlanLabel } from '../../lib/plans'

export function Moderation() {
  return (
    <UpgradeNotice
      icon="🛡️"
      title="Moderação avançada"
      sub="Auto-moderação, anti-raid e deteção de spam configuráveis. Bans, warns e logs básicos já estão disponíveis em Membros."
      requiredPlanLabel={minPlanLabel('automod')}
    />
  )
}
