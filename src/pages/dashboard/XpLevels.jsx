import { UpgradeNotice } from '../../components/dashboard/UpgradeNotice'
import { minPlanLabel } from '../../lib/plans'

export function XpLevels() {
  return (
    <UpgradeNotice
      icon="🎮"
      title="XP & Níveis"
      sub="Sistema de progressão com recompensas configuráveis — roles, canais exclusivos e badges."
      requiredPlanLabel={minPlanLabel('xp_levels')}
    />
  )
}
