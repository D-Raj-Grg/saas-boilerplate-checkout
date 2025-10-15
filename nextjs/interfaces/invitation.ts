export interface CreateInvitationData {
  email: string;
  organization_uuid?: string;
  workspace_uuid?: string;
  role: string;
  message?: string;
  expires_at?: string;
}

export interface Invitation {
  uuid: string;
  email: string;
  role: string;
  status: 'pending' | 'accepted' | 'rejected' | 'expired';
  message?: string;
  expires_at: string;
  organization_uuid?: string;
  workspace_uuid?: string;
  created_at: string;
  updated_at: string;
}

export interface InvitationWithDetails extends Invitation {
  organization?: {
    uuid: string;
    name: string;
    slug: string;
  };
  workspace?: {
    uuid: string;
    name: string;
    slug: string;
    organization: {
      uuid: string;
      name: string;
    };
  };
  inviter: {
    name: string;
    email: string;
  };
}

export interface AcceptInvitationData {
  token: string;
}

export interface RejectInvitationData {
  token: string;
}

export interface InvitationStatistics {
  total_sent: number;
  total_pending: number;
  total_accepted: number;
  total_rejected: number;
  total_expired: number;
}