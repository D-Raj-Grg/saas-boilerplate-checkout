"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { useUserStore } from "@/stores/user-store";
import { OrganizationSwitcherDialog } from "./organization-switcher-dialog";
import { cn } from "@/lib/utils";
import { ChevronsUpDownIcon } from "lucide-react";

export function OrganizationWorkspaceSwitcher({ collapsed }: { collapsed: boolean }) {
  const [orgSwitcherOpen, setOrgSwitcherOpen] = useState(false);
  const { selectedOrganization, selectedWorkspace } = useUserStore();

  return (
    <>
      <Button
        variant="ghost"
        className={cn("w-full justify-between text-left hover:bg-white text-foreground p-0 h-auto min-h-[56px] max-w-[280px] px-2 border shadow-sm ", collapsed && "border-none hover:bg-transparent flex justify-center items-center !px-0")}
        onClick={() => setOrgSwitcherOpen(true)}
      >
        <div className="flex justify-between items-center gap-4 w-full">

        <div className={cn("flex items-center gap-3 min-w-0 flex-1", collapsed && "justify-center gap-0")}>
          <div className="size-10 rounded-full border  text-secondary flex items-center justify-center text-sm font-bold flex-shrink-0">
            {selectedOrganization?.name?.charAt(0) || "A"}
          </div>
          <div className={cn("min-w-0 flex-1 overflow-hidden", collapsed && "hidden")}>
            <div className="font-medium text-foreground truncate text-sm leading-tight">
              {selectedOrganization?.name || "Organization"}
            </div>
            <div className="text-xs text-muted-foreground truncate leading-tight mt-0.5">
              {selectedWorkspace?.name || "Workspace"}
            </div>
          </div>
        </div>

        <ChevronsUpDownIcon className={cn("size-4 text-muted-foreground hover:text-black" , collapsed && "hidden")}/>

        </div>

      </Button>

      <OrganizationSwitcherDialog
        open={orgSwitcherOpen}
        onOpenChange={setOrgSwitcherOpen}
      />
    </>
  );
}