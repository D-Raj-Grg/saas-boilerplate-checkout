import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

export default function WorkspaceLoading() {
  return (
    <div className="max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-64" />
          <Skeleton className="h-4 w-96" />
        </div>
        <div className="flex items-center gap-2">
          <Skeleton className="h-10 w-36" />
          <Skeleton className="h-10 w-10" />
        </div>
      </div>

      {/* Members Table */}
      <div className="border rounded-lg overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>
                <Skeleton className="h-4 w-12" />
              </TableHead>
              <TableHead className="text-right">
                <Skeleton className="h-4 w-12" />
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {/* Member rows */}
            {[...Array(4)].map((_, i) => (
              <TableRow key={i}>
                <TableCell>
                  <div className="flex items-center gap-3">
                    <Skeleton className="h-10 w-10 rounded-full" />
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-32" />
                      <Skeleton className="h-3 w-48" />
                    </div>
                  </div>
                </TableCell>
                <TableCell className="text-right">
                  <div className="flex items-center justify-end gap-2">
                    <Skeleton className="h-4 w-16" />
                    <Skeleton className="h-8 w-8" />
                  </div>
                </TableCell>
              </TableRow>
            ))}

            {/* Pending Invitations Section Header */}
            <TableRow>
              <TableCell colSpan={2} className="bg-gray-50">
                <Skeleton className="h-4 w-36" />
              </TableCell>
            </TableRow>

            {/* Invitation rows */}
            {[...Array(2)].map((_, i) => (
              <TableRow key={i}>
                <TableCell>
                  <div className="flex items-center gap-3">
                    <Skeleton className="h-10 w-10 rounded-full" />
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-16" />
                      <Skeleton className="h-3 w-44" />
                    </div>
                  </div>
                </TableCell>
                <TableCell className="text-right">
                  <div className="flex items-center justify-end gap-2">
                    <Skeleton className="h-4 w-16" />
                    <Skeleton className="h-8 w-8" />
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}