import { create } from "zustand";
import { persist } from "zustand/middleware";
import { subscribeWithSelector } from "zustand/middleware";
import { 
  UserOrganization, 
  UserWorkspace, 
  UserStore 
} from "@/interfaces/user";
import { PlanLimits } from "@/types/plan";

interface ExtendedUserStore extends UserStore {
  organizations: UserOrganization[];
  workspaces: UserWorkspace[];
  pendingInvitations: any[];
  isLoading: boolean;
  planLimits?: PlanLimits;
}

export const useUserStore = create<ExtendedUserStore>()(
  subscribeWithSelector(
    persist(
      (set, get) => ({
        // Legacy fields for compatibility
        userData: null,
        selectedOrganization: null,
        selectedWorkspace: null,
        isInitialized: false,
        
        // New fields matching reference architecture
        organizations: [],
        workspaces: [],
        pendingInvitations: [],
        isLoading: true,
        planLimits: undefined,
      
      // Simple: Set user data and auto-select from preferences or current context
      setUserData: (userData) => {
        set({ 
          userData,
          // Also update new fields
          organizations: userData?.organizations || [],
          workspaces: userData?.workspaces || [],
          planLimits: userData?.current_organization_plan_limits,
          pendingInvitations: userData?.current_workspace_pending_invitations || [],
          isLoading: false
        });
        
        // Auto-select from user's current organization and workspace
        if (userData?.user) {
          const { current_organization_uuid, current_workspace_uuid } = userData.user;
          
          if (current_organization_uuid) {
            const org = userData.organizations?.find(o => o.uuid === current_organization_uuid);
            if (org) {
              set({ selectedOrganization: org });
              
              if (current_workspace_uuid) {
                const workspace = userData.workspaces?.find(w => w.uuid === current_workspace_uuid);
                if (workspace) {
                  set({ selectedWorkspace: workspace });
                }
              }
            }
          }
        }
      },
      
      clearUser: () => set({ 
        userData: null, 
        selectedOrganization: null, 
        selectedWorkspace: null,
        isInitialized: false,
        organizations: [],
        workspaces: [],
        pendingInvitations: [],
        isLoading: false,
        planLimits: undefined
      }),
      
      setSelectedOrganization: (organization) => set({ 
        selectedOrganization: organization,
        selectedWorkspace: null 
      }),
      
      setSelectedWorkspace: (workspace) => set({ selectedWorkspace: workspace }),
      
      setIsInitialized: (initialized) => set({ isInitialized: initialized }),
      
      // Simple getter for backward compatibility
      get user() {
        return get().userData?.user || null;
      },
      
      // Simple methods for backward compatibility
      setUser: (user) => {
        const { userData } = get();
        if (userData && user) {
          set({ 
            userData: { 
              ...userData, 
              user: {
                name: user.name,
                first_name: user.first_name,
                last_name: user.last_name,
                email: user.email,
                email_verified_at: user.email_verified_at,
                current_organization_uuid: user.current_organization_uuid,
                current_workspace_uuid: user.current_workspace_uuid,
              }
            }
          });
        }
      },
      
      updateUserOrganizations: (organizations) => {
        const { userData } = get();
        if (userData) {
          set({ userData: { ...userData, organizations } });
        }
      },
      
      updateUserWorkspaces: (workspaces) => {
        const { userData } = get();
        if (userData) {
          set({ userData: { ...userData, workspaces } });
        }
      },
      
      getInitials: () => {
        const userData = get().userData;
        if (!userData?.user) return "?";
        
        const user = userData.user;
        const firstInitial = user.first_name?.charAt(0)?.toUpperCase() || "";
        const lastInitial = user.last_name?.charAt(0)?.toUpperCase() || "";
        
        // If we have initials from name, use them
        if (firstInitial || lastInitial) {
          return firstInitial + lastInitial;
        }
        
        // Otherwise, use email initial if email exists
        return user.email?.charAt(0)?.toUpperCase() || "?";
      },
    }),
    {
      name: "user-storage",
      // Only persist essential data to avoid heavy serialization
      partialize: (state) => ({
        selectedOrganization: state.selectedOrganization,
        selectedWorkspace: state.selectedWorkspace,
        isInitialized: state.isInitialized,
      }),
    }
  )
  )
);

// Optimized selectors to prevent unnecessary re-renders
export const useUser = () => useUserStore((state) => state.userData?.user || null);
export const useUserData = () => useUserStore((state) => state.userData);
export const useOrganizations = () => useUserStore((state) => state.userData?.organizations || []);
export const useWorkspaces = () => useUserStore((state) => state.userData?.workspaces || []);
export const useSelectedOrganization = () => useUserStore((state) => state.selectedOrganization);
export const useSelectedWorkspace = () => useUserStore((state) => state.selectedWorkspace);
export const useIsInitialized = () => useUserStore((state) => state.isInitialized);
export const usePendingInvitations = () => useUserStore((state) => state.userData?.current_workspace_pending_invitations || []);
export const useUserInitials = () => useUserStore((state) => state.getInitials());
export const usePlanLimits = () => useUserStore((state) => state.planLimits);

// Actions only
export const useUserActions = () => useUserStore((state) => ({
  setUserData: state.setUserData,
  clearUser: state.clearUser,
  setSelectedOrganization: state.setSelectedOrganization,
  setSelectedWorkspace: state.setSelectedWorkspace,
  setIsInitialized: state.setIsInitialized,
  setUser: state.setUser,
  updateUserOrganizations: state.updateUserOrganizations,
  updateUserWorkspaces: state.updateUserWorkspaces,
}));