<?php

class AdminKYCController
{
    private KYCService $kycService;

    public function __construct(KYCService $kycService)
    {
        $this->kycService = $kycService;
    }

    public function review(int $verificationId, string $status, string $notes, int $adminId): array
    {
        return $this->kycService->reviewVerification($verificationId, $status, $notes, $adminId);
    }

    public function pendingRequests(): array
    {
        return $this->kycService->getPendingVerifications();
    }
}
