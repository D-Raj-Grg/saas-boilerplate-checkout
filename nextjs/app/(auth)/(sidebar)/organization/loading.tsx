import { Skeleton } from "@/components/ui/skeleton";
import { Card, CardContent } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

export default function OrganizationsLoading() {
  return (
    <div className="space-y-4 max-w-7xl mx-auto">
      {/* Organization Header */}
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-64" /> {/* Organization name */}
          <Skeleton className="h-4 w-96" /> {/* Description */}
        </div>
        <Skeleton className="h-10 w-10" /> {/* Dropdown menu button */}
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {[...Array(3)].map((_, i) => (
          <Card key={i} className="relative overflow-hidden py-2">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <Skeleton className="h-4 w-32" /> {/* Card label */}
                  </div>
                  <div className="space-y-1">
                    <Skeleton className="h-9 w-16" /> {/* Large number */}
                    <Skeleton className="h-3 w-24" /> {/* Subtitle */}
                  </div>
                </div>
                <div className="flex items-center justify-center">
                  <Skeleton className="h-12 w-12 rounded-full" /> {/* Icon */}
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Manage Workspaces Section */}
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Skeleton className="h-7 w-48" /> {/* Section title */}
          <Skeleton className="h-10 w-36" /> {/* New Workspace button */}
        </div>

        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>
                  <Skeleton className="h-4 w-24" />
                </TableHead>
                <TableHead className="text-right">
                  <Skeleton className="h-4 w-20" />
                </TableHead>
                <TableHead className="text-right">
                  <Skeleton className="h-4 w-28" />
                </TableHead>
                <TableHead className="text-right">
                  <Skeleton className="h-4 w-20" />
                </TableHead>
                <TableHead className="text-right">
                  <Skeleton className="h-4 w-16" />
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {[...Array(3)].map((_, i) => (
                <TableRow key={i}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <Skeleton className="h-8 w-8 rounded-full" />
                      <Skeleton className="h-4 w-32" />
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-8 ml-auto" />
                      <Skeleton className="h-3 w-20 ml-auto" />
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-6 ml-auto" />
                      <Skeleton className="h-3 w-20 ml-auto" />
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="space-y-1">
                      <Skeleton className="h-4 w-6 ml-auto" />
                      <Skeleton className="h-3 w-20 ml-auto" />
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex items-center justify-end gap-2">
                      <Skeleton className="h-8 w-8" />
                      <Skeleton className="h-8 w-8" />
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>
    </div>
  );
}