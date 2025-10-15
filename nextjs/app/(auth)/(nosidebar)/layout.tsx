export default function NoSidebarLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-white h-full overflow-auto">
      {children}
    </div>
  );
}