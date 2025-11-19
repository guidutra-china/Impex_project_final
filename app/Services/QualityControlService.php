<?php

namespace App\Services;

use App\Models\QualityInspection;
use App\Models\QualityInspectionItem;
use App\Models\QualityInspectionCheckpoint;
use App\Models\QualityCheckpoint;
use App\Models\QualityCertificate;
use Illuminate\Support\Facades\DB;

class QualityControlService
{
    /**
     * Create quality inspection
     *
     * @param string $inspectableType
     * @param int $inspectableId
     * @param string $inspectionType
     * @param array $data
     * @return QualityInspection
     */
    public function createInspection(
        string $inspectableType,
        int $inspectableId,
        string $inspectionType,
        array $data = []
    ): QualityInspection {
        return DB::transaction(function () use ($inspectableType, $inspectableId, $inspectionType, $data) {
            $inspection = QualityInspection::create([
                'inspection_number' => $this->generateInspectionNumber(),
                'inspectable_type' => $inspectableType,
                'inspectable_id' => $inspectableId,
                'inspection_type' => $inspectionType,
                'status' => 'pending',
                'inspection_date' => $data['inspection_date'] ?? now(),
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'inspector_name' => $data['inspector_name'] ?? auth()->user()->name,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Load applicable checkpoints
            $this->loadCheckpoints($inspection);

            return $inspection->load('checkpoints');
        });
    }

    /**
     * Load applicable checkpoints for inspection
     *
     * @param QualityInspection $inspection
     * @return void
     */
    private function loadCheckpoints(QualityInspection $inspection): void
    {
        // Get active checkpoints
        $checkpoints = QualityCheckpoint::active()->get();

        foreach ($checkpoints as $checkpoint) {
            QualityInspectionCheckpoint::create([
                'quality_inspection_id' => $inspection->id,
                'quality_checkpoint_id' => $checkpoint->id,
                'result' => 'pending',
            ]);
        }
    }

    /**
     * Update checkpoint result
     *
     * @param int $inspectionCheckpointId
     * @param string $result
     * @param array $data
     * @return QualityInspectionCheckpoint
     */
    public function updateCheckpoint(int $inspectionCheckpointId, string $result, array $data = []): QualityInspectionCheckpoint
    {
        $checkpoint = QualityInspectionCheckpoint::findOrFail($inspectionCheckpointId);

        $checkpoint->update([
            'result' => $result,
            'measured_value' => $data['measured_value'] ?? null,
            'expected_value' => $data['expected_value'] ?? null,
            'notes' => $data['notes'] ?? null,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);

        return $checkpoint;
    }

    /**
     * Complete inspection
     *
     * @param QualityInspection $inspection
     * @param string $result ('passed' or 'failed')
     * @param string|null $failureReason
     * @param string|null $correctiveAction
     * @return QualityInspection
     */
    public function completeInspection(
        QualityInspection $inspection,
        string $result,
        ?string $failureReason = null,
        ?string $correctiveAction = null
    ): QualityInspection {
        $inspection->update([
            'status' => 'completed',
            'result' => $result,
            'completed_date' => now(),
            'failure_reason' => $failureReason,
            'corrective_action' => $correctiveAction,
        ]);

        // If passed, generate certificate
        if ($result === 'passed') {
            $this->generateCertificate($inspection);
        }

        return $inspection->fresh();
    }

    /**
     * Generate quality certificate
     *
     * @param QualityInspection $inspection
     * @return QualityCertificate
     */
    public function generateCertificate(QualityInspection $inspection): QualityCertificate
    {
        return QualityCertificate::create([
            'quality_inspection_id' => $inspection->id,
            'certificate_number' => $this->generateCertificateNumber(),
            'certificate_type' => 'quality_assurance',
            'issue_date' => now(),
            'status' => 'valid',
        ]);
    }

    /**
     * Add inspection items
     *
     * @param QualityInspection $inspection
     * @param array $items [['product_id' => 1, 'quantity_inspected' => 10], ...]
     * @return QualityInspection
     */
    public function addInspectionItems(QualityInspection $inspection, array $items): QualityInspection
    {
        foreach ($items as $item) {
            QualityInspectionItem::create([
                'quality_inspection_id' => $inspection->id,
                'product_id' => $item['product_id'],
                'quantity_inspected' => $item['quantity_inspected'],
                'quantity_passed' => $item['quantity_passed'] ?? 0,
                'quantity_failed' => $item['quantity_failed'] ?? 0,
                'result' => $item['result'] ?? 'pending',
                'defects_found' => $item['defects_found'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return $inspection->fresh('items');
    }

    /**
     * Fail inspection
     *
     * @param QualityInspection $inspection
     * @param string $reason
     * @param string|null $correctiveAction
     * @return QualityInspection
     */
    public function failInspection(
        QualityInspection $inspection,
        string $reason,
        ?string $correctiveAction = null
    ): QualityInspection {
        return $this->completeInspection($inspection, 'failed', $reason, $correctiveAction);
    }

    /**
     * Pass inspection
     *
     * @param QualityInspection $inspection
     * @return QualityInspection
     */
    public function passInspection(QualityInspection $inspection): QualityInspection
    {
        return $this->completeInspection($inspection, 'passed');
    }

    /**
     * Generate unique inspection number
     *
     * @return string
     */
    private function generateInspectionNumber(): string
    {
        $year = date('Y');
        $lastInspection = QualityInspection::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInspection ? (int) substr($lastInspection->inspection_number, -4) + 1 : 1;

        return sprintf('QI-%s-%04d', $year, $nextNumber);
    }

    /**
     * Generate unique certificate number
     *
     * @return string
     */
    private function generateCertificateNumber(): string
    {
        $year = date('Y');
        $lastCertificate = QualityCertificate::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastCertificate ? (int) substr($lastCertificate->certificate_number, -4) + 1 : 1;

        return sprintf('QC-%s-%04d', $year, $nextNumber);
    }
}
