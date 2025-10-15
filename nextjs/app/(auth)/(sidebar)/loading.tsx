import { Skeleton } from "@/components/ui/skeleton";

// Skeleton Sidebar Component
function SkeletonSidebar({ collapsed = false }: { collapsed?: boolean }) {
  return (
    <aside
      className={`fixed left-0 top-0 h-screen flex flex-col bg-sidebar text-sidebar-foreground transition-all duration-300 overflow-hidden z-40 overflow-y-auto ${
        collapsed ? "w-16" : "w-64"
      }`}
      role="navigation"
      aria-label="Main navigation skeleton"
    >
      <div className="flex h-full flex-1 flex-col justify-between">
        <div className="relative z-10 flex flex-col">
          {/* Logo Section */}
          <div className={`flex h-full items-center px-4 py-6 transition-all duration-300 ease-in-out ${collapsed ? "justify-center" : "px-6 justify-start"}`}>
            <Skeleton className={collapsed ? "h-8 w-8 rounded-full" : "h-8 w-32 rounded-md"} />
          </div>

          {/* Organization/Workspace Switcher */}
          <div className={`space-y-2 ${collapsed ? "px-3" : "px-4"}`}>
            <Skeleton className={collapsed ? "h-10 w-10 rounded-md mx-auto" : "h-10 w-full rounded-md"} />
          </div>

          <div className={collapsed ? "px-3" : "px-4"}>
            <Skeleton className="w-full h-px bg-border" />
          </div>

          {/* Main Navigation */}
          <nav className={`space-y-2 ${collapsed ? "px-3" : "px-4"}`}>
            {[...Array(3)].map((_, i) => (
              <div key={i} className={`flex items-center ${collapsed ? "justify-center p-2.5" : "gap-3 px-3 py-2"}`}>
                <Skeleton className="h-5 w-5 rounded-sm" />
                {!collapsed && <Skeleton className="h-4 w-20 rounded-sm" />}
              </div>
            ))}
          </nav>

          <div className={collapsed ? "px-3" : "px-4"}>
            <Skeleton className="w-full h-px bg-border" />
          </div>

          {/* Bottom Navigation */}
          <div className={`space-y-2 ${collapsed ? "px-3" : "px-4"}`}>
            {[...Array(3)].map((_, i) => (
              <div key={i} className={`flex items-center ${collapsed ? "justify-center p-2.5" : "gap-3 px-3 py-2"}`}>
                <Skeleton className="h-5 w-5 rounded-sm" />
                {!collapsed && <Skeleton className="h-4 w-16 rounded-sm" />}
              </div>
            ))}
          </div>
        </div>

        {/* Usage Stats Section */}
        {!collapsed && (
          <div className="p-4">
            <div className="bg-white border border-border rounded-md py-3 px-4 shadow-md">
              <Skeleton className="h-5 w-24 rounded-sm mb-2" />
              <Skeleton className="w-full h-px bg-border mb-3" />

              <div className="space-y-5">
                {[...Array(2)].map((_, i) => (
                  <div key={i} className="space-y-2">
                    <div className="flex items-center justify-between">
                      <Skeleton className="h-3 w-16 rounded-sm" />
                      <Skeleton className="h-3 w-12 rounded-sm" />
                    </div>
                    <Skeleton className="w-full h-1 rounded-full" />
                  </div>
                ))}
              </div>

              <div className="flex items-center justify-center gap-1 mt-3">
                <Skeleton className="h-4 w-20 rounded-sm" />
                <Skeleton className="h-4 w-4 rounded-sm" />
              </div>
            </div>
          </div>
        )}
      </div>
    </aside>
  );
}

// Skeleton Topbar Component
function SkeletonTopbar() {
  return (
    <header className="h-16 border-b bg-card flex items-center justify-between px-4 md:px-6">
      <div className="flex items-center gap-2 md:gap-4 min-w-0 flex-1">
        <Skeleton className="h-8 w-8 rounded-md" />
      </div>

      <div className="flex items-center gap-3">
        <Skeleton className="h-10 w-10 rounded-md" />
        <Skeleton className="h-9 w-9 rounded-full" />
      </div>
    </header>
  );
}

// Generic Content Skeleton for Sidebar Routes
function SkeletonContent() {
  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <Skeleton className="h-8 w-64 rounded-md mb-2" />
          <Skeleton className="h-4 w-96 rounded-sm" />
        </div>
        <Skeleton className="h-10 w-36 rounded-md" />
      </div>

      {/* Content Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="p-6 border rounded-lg bg-card">
            <div className="space-y-2">
              <Skeleton className="h-4 w-24 rounded-sm" />
              <Skeleton className="h-8 w-16 rounded-md" />
              <Skeleton className="h-3 w-32 rounded-sm" />
            </div>
          </div>
        ))}
      </div>

      {/* Main Content Area */}
      <div className="p-6 border rounded-lg bg-card">
        <div className="space-y-4">
          <Skeleton className="h-6 w-48 rounded-sm" />
          <Skeleton className="h-4 w-full rounded-sm" />
          <Skeleton className="h-4 w-full rounded-sm" />
          <Skeleton className="h-4 w-3/4 rounded-sm" />
        </div>
      </div>
    </div>
  );
}

export default function SidebarLayoutLoading() {
  return (
    <div className="h-screen bg-sidebar relative overflow-hidden">
      {/* Sidebar */}
      <SkeletonSidebar collapsed={false} />

      {/* Main Content Area */}
      <div className="absolute inset-2 bg-background transition-all duration-300 flex flex-col overflow-hidden border left-[256px] rounded-2xl">
        {/* Topbar */}
        <SkeletonTopbar />

        {/* Page Content */}
        <main className="flex-1 overflow-y-auto overflow-x-hidden">
          <div className="px-4 py-6 lg:px-6 lg:py-8">
            <SkeletonContent />
          </div>
        </main>
      </div>
    </div>
  );
}