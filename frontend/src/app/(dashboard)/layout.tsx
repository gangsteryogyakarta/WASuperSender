import { Sidebar } from "@/components/layout/sidebar"
import { Toaster } from "sonner"

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <div className="flex h-screen overflow-hidden bg-background">
      <Sidebar />
      <main className="flex-1 overflow-y-auto p-8">
        {children}
      </main>
      <Toaster />
    </div>
  )
}
