
type InfoCardProps = {
  title: string
  value?: string | null
  fallback: string
  badge: string
  badgeClasses: string
  Icon: React.ElementType
}

export default function DashboardInfoCard({ title, value, fallback, badge, badgeClasses, Icon }: InfoCardProps) {
  return (
    <div className="flex items-center justify-between p-4 rounded-md border w-full">
      <div className="flex items-center gap-3 flex-1">
        <Icon className="h-5 w-5 text-muted-foreground" />
        <div className="flex-1">
          <h4 className="font-medium">{title}</h4>
          <div className="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
            <span>{value || fallback}</span>
            <span
              className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${badgeClasses}`}
            >
              {badge}
            </span>
          </div>
        </div>
      </div>
    </div>
  )
}