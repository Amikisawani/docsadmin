// ==================== UTILISATEUR ====================
export interface User {
    id: string;
    name: string;
    email: string;
    avatar_path: string | null;
    avatar_url: string | null;
    department_id: string | null;
    department?: Department;
    job_title: string | null;
    phone: string | null;
    is_active: boolean;
    signature_path: string | null;
    signature_image_path: string | null;
    signature_url: string | null;
    status: string;
    roles: Role[];
    permissions: string[];
    created_at: string;
    updated_at: string;
}

// ==================== RÔLES & PERMISSIONS ====================
export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions: Permission[];
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
}

// ==================== DÉPARTEMENT ====================
export interface Department {
    id: string;
    name: string;
    code: string;
    parent_id: string | null;
    parent?: Department;
    children?: Department[];
    type: 'ministere' | 'departement' | 'direction' | 'service' | 'unite';
    description: string | null;
    is_active: boolean;
    users_count?: number;
    created_at: string;
}

// ==================== DOCUMENT ====================
export interface Document {
    id: string;
    document_number: string;
    reference: string | null;
    subject: string;
    document_type: DocumentType;
    author_id: string;
    author?: User;
    department_id: string | null;
    department?: Department;
    version: string;
    status: DocumentStatus;
    flow_type: 'unique' | 'mail_merge';
    is_mail_merge: boolean;
    confidentiality: ConfidentialityLevel;
    document_date: string;
    content: string | null;
    hash: string | null;
    qr_code_path: string | null;
    workflow_id: string | null;
    workflow?: Workflow;
    current_workflow_instance_id: string | null;
    currentWorkflowInstance?: WorkflowInstance;
    source_file_path: string | null;
    signed_pdf_path: string | null;
    submitted_for_signature_at: string | null;
    priority: 'normale' | 'haute' | 'urgente';
    deadline: string | null;
    rejection_reason: string | null;
    recalled_at: string | null;
    is_archived: boolean;
    is_deleted: boolean;
    attachments?: DocumentAttachment[];
    signatures?: DocumentSignature[];
    histories?: DocumentHistory[];
    workflowInstances?: WorkflowInstance[];
    created_at: string;
    updated_at: string;
}

// ==================== PROGRESSION WORKFLOW ====================
export interface WorkflowProgress {
    instance: WorkflowInstance;
    workflow: Workflow;
    total_steps: number;
    completed_steps: number;
    current_step: string;
    status: string;
    pending_approvals: Array<{
        approval_id: string;
        step_name: string;
        approver: { id: string; name: string; email: string } | null;
        created_at: string;
    }>;
    all_approvals: Array<{
        approval_id: string;
        step_name: string;
        approver: { id: string; name: string; email: string } | null;
        status: string;
        action_at: string | null;
        comment: string | null;
    }>;
}

export type DocumentType =
    | 'courrier_entrant' | 'courrier_sortant' | 'note' | 'notification'
    | 'decision' | 'arrete' | 'decret' | 'circulaire'
    | 'proces_verbal' | 'rapport' | 'contrat' | 'convention'
    | 'demande' | 'conge' | 'mission' | 'facture' | 'autre';

export type DocumentStatus = 'draft' | 'pending' | 'approved' | 'rejected' | 'signed' | 'archived';
export type ConfidentialityLevel = 'public' | 'interne' | 'confidentiel' | 'secret';

// ==================== HISTORIQUE ====================
export interface DocumentHistory {
    id: string;
    document_id: string;
    user_id: string;
    user?: User;
    action: string;
    details: any;
    ip_address: string | null;
    created_at: string;
}

// ==================== PIÈCE JOINTE ====================
export interface DocumentAttachment {
    id: string;
    document_id: string;
    original_name: string;
    stored_path: string;
    mime_type: string;
    size: number;
    hash: string;
    type: string;
    uploaded_by: string;
    created_at: string;
}

// ==================== SIGNATURE ====================
export interface Signature {
    id: string;
    user_id: string;
    user?: User;
    type: 'graphical' | 'digital' | 'certificate';
    label: string | null;
    image_path: string | null;
    certificate_data: string | null;
    certificate_serial: string | null;
    certificate_expires_at: string | null;
    is_default: boolean;
    is_active: boolean;
    metadata: any;
    created_at: string;
}

export interface DocumentSignature {
    id: string;
    document_id: string;
    signature_id: string;
    signature?: Signature;
    signed_by: string;
    signer?: User;
    type: string;
    hash_signature: string | null;
    signed_at: string;
    position: any;
}

// ==================== WORKFLOW ====================
export interface Workflow {
    id: string;
    name: string;
    description: string | null;
    document_type: string;
    steps: WorkflowStep[];
    is_active: boolean;
    created_by: string;
    creator?: User;
    instances?: WorkflowInstance[];
    created_at: string;
}

export interface WorkflowStep {
    name: string;
    role: string;
    user_id?: string;
    order: number;
}

export interface WorkflowInstance {
    id: string;
    workflow_id: string;
    workflow?: Workflow;
    document_id: string;
    document?: Document;
    current_step: string;
    status: 'in_progress' | 'completed' | 'rejected' | 'cancelled';
    history: any;
    initiated_by: string;
    initiator?: User;
    approvals?: WorkflowApproval[];
    completed_at: string | null;
    created_at: string;
}

export interface WorkflowApproval {
    id: string;
    workflow_instance_id: string;
    workflowInstance?: WorkflowInstance;
    step_name: string;
    approver_id: string;
    approver?: User;
    status: 'pending' | 'approved' | 'rejected';
    comment: string | null;
    signature_path: string | null;
    action_at: string | null;
    created_at: string;
}

// ==================== ARCHIVE ====================
export interface ArchiveBox {
    id: string;
    code: string;
    name: string;
    category: string | null;
    description: string | null;
    location: string | null;
    capacity: number | null;
    status: 'active' | 'full' | 'archived';
    created_by: string;
    creator?: User;
    archives?: Archive[];
    created_at: string;
}

export interface Archive {
    id: string;
    document_id: string;
    document?: Document;
    archive_box_id: string | null;
    archiveBox?: ArchiveBox;
    reference: string;
    category: string | null;
    conservation_duration: string | null;
    archived_at: string;
    conservation_until: string | null;
    status: 'active' | 'destroyed' | 'transferred';
    notes: string | null;
    archived_by: string;
    archiver?: User;
    created_at: string;
}

// ==================== NOTIFICATION ====================
export interface AppNotification {
    id: string;
    type: string;
    title: string;
    body: string | null;
    data: any;
    is_read: boolean;
    read_at: string | null;
    created_at: string;
}

// ==================== TEMPLATE ====================
export interface Template {
    id: string;
    name: string;
    type: 'word' | 'pdf';
    category: string | null;
    description: string | null;
    file_path: string;
    variables: Record<string, string> | null;
    created_by: string;
    creator?: User;
    is_active: boolean;
    created_at: string;
}

// ==================== AUDIT ====================
export interface AuditLog {
    id: string;
    description: string;
    causer_id: string | null;
    causer?: User;
    subject_type: string | null;
    subject_id: string | null;
    properties: any;
    created_at: string;
}

// ==================== PUBLIPOSTAGE (MAIL MERGE) ====================
export type MailMergeStatus =
    | 'processing'
    | 'completed'
    | 'partial'
    | 'failed'
    | 'awaiting_workflow'
    | 'pending_signature'
    | 'signed'
    | 'rejected';

export interface MailMergeRecipient {
    id: string;
    batch_id: string;
    name: string;
    destinataire?: string | null;
    variables: Record<string, string>;
    output_path: string | null;
status: 'pending' | 'generated' | 'failed' | 'signed';
    error: string | null;
    generated_at: string | null;
    created_at: string;
}

export interface MailMergeBatch {
    id: string;
    template_id: string;
    template?: Template;
    document_id: string | null;
    document?: Document;
    workflow_id: string | null;
    workflow?: Workflow;
    current_workflow_instance_id: string | null;
    currentWorkflowInstance?: WorkflowInstance;
    created_by: string;
    creator?: User;
    title: string | null;
    status: MailMergeStatus;
    status_label: string;
    total_recipients: number;
    generated_count: number;
    failed_count: number;
    zip_path: string | null;
    format: 'pdf' | 'docx' | 'txt';
errors: any;
    completed_at: string | null;
    submitted_for_signature_at: string | null;
    signed_at: string | null;
    signed_by: string | null;
    signature_id: string | null;
    signed_zip_path: string | null;
    rejection_reason: string | null;
    recipients?: MailMergeRecipient[];
    created_at: string;
    updated_at: string;
}

// ==================== API ====================
export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface LoginResponse {
    user: User;
    token: string;
}

export interface DashboardStats {
    total_documents: number;
    draft_documents: number;
    pending_documents: number;
    approved_documents: number;
    signed_documents: number;
    rejected_documents: number;
    archived_documents: number;
    total_users: number;
    active_users: number;
    total_departments: number;
    pending_approvals: number;
    active_workflows: number;
    documents_by_type: Record<string, number>;
    documents_by_month: Record<string, number>;
    documents_by_confidentiality: Record<string, number>;
    recent_activities: AuditLog[];
}