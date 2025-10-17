<?php

declare(strict_types=1);

require_once MODEL . 'QuarterlyStatistics.php';
require_once MODEL . 'ActivityLogs.php';

/**
 * QuarterlyStatisticsController
 * 
 * Handles operations related to the quarterly_statistics view
 * Provides endpoints for retrieving statistical data about PWDs
 */
class QuarterlyStatisticsController
{
    /**
     * Get annual registration report for the current year
     * Endpoint: /v1/statistics/current-year
     * @return string JSON response with annual registration metrics
     */
    public function getAnnualRegistrationReport(): string
    {
        $currentYear = date('Y');
        // Total new PWDs registered in the current year
        $db = (new \PwdRecords())->db;
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM pwd_records WHERE year = :year");
        $stmt->bindValue(':year', $currentYear, PDO::PARAM_INT);
        $stmt->execute();
        $totalRegistrations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Total unique PWDs who have received assistance in the current year
        $stmt = $db->prepare("SELECT COUNT(DISTINCT beneficiary_id) as count FROM assistance_requests WHERE YEAR(created_at) = :year AND status IN ('approved','assessed')");
        $stmt->bindValue(':year', $currentYear, PDO::PARAM_INT);
        $stmt->execute();
        $totalAssisted = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        // Total pending assistance requests in the current year
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM assistance_requests WHERE YEAR(created_at) = :year AND status = 'pending'");
        $stmt->bindValue(':year', $currentYear, PDO::PARAM_INT);
        $stmt->execute();
        $pendingRequests = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

        $metrics = [
            [
                'metric' => 'Total Registrations (Current Year)',
                'value' => $totalRegistrations
            ],
            [
                'metric' => 'Total Assisted (Current Year)',
                'value' => $totalAssisted
            ],
            [
                'metric' => 'Pending Assistance Requests',
                'value' => $pendingRequests
            ]
        ];

        return json_encode([
            'status' => 'success',
            'data' => $metrics,
            'message' => 'Annual registration report generated successfully'
        ], JSON_PRETTY_PRINT);
    }
    /**
     * Get quarterly registration report for the current year
     * Endpoint: /v1/quarterly-statistics/report
     * @return string JSON response with quarterly registration data for the current year
     */
    public function getQuarterlyRegistrationReport(): string
    {
        $currentYear = date('Y');
        // Get all quarters for the current year
        $statistics = $this->statsModel->getCurrentYearStatistics();
        // Optionally, sort by quarter (Q1, Q2, Q3, Q4)
        $quarterOrder = ['Q1' => 1, 'Q2' => 2, 'Q3' => 3, 'Q4' => 4];
        usort($statistics, function($a, $b) use ($quarterOrder) {
            return ($quarterOrder[$a['quarter']] ?? 0) <=> ($quarterOrder[$b['quarter']] ?? 0);
        });
        return json_encode([
            'status' => !empty($statistics) ? 'success' : 'error',
            'data' => $statistics,
            'message' => empty($statistics) ? "No quarterly registration data found for $currentYear" : "Quarterly registration data for $currentYear retrieved successfully"
        ], JSON_PRETTY_PRINT);
    }

    protected QuarterlyStatistics $statsModel;
    protected ActivityLogs $logsModel;

    public function __construct()
    {
        $this->statsModel = new QuarterlyStatistics();
        $this->logsModel = new ActivityLogs();
    }

    /**
     * Get all quarterly statistics
     * 
     * @return string JSON response with all statistics
     */
    public function getAllStatistics(): string
    {
        $statistics = $this->statsModel->getAllStatistics();

        return json_encode([
            'status' => !empty($statistics) ? 'success' : 'error',
            'data' => $statistics,
            'message' => empty($statistics) ? 'No statistics found' : 'Quarterly statistics retrieved successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get statistics for a specific period (quarter and year)
     * 
     * @param string $quarter The quarter (Q1, Q2, Q3, Q4)
     * @param int|string $year The year
     * @return string JSON response with period statistics
     */
    public function getStatisticsByPeriod(string $quarter, $year): string
    {
        // Validate quarter format
        if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'Invalid quarter format. Must be Q1, Q2, Q3, or Q4'
            ], JSON_PRETTY_PRINT);
        }

        // Convert year to integer
        $yearInt = (int)$year;

        // Get statistics for the period
        $statistics = $this->statsModel->getStatisticsByPeriod($quarter, $yearInt);

        if ($statistics === null) {
            return json_encode([
                'status' => 'error',
                'message' => "No statistics found for $quarter $year"
            ], JSON_PRETTY_PRINT);
        }

        // Log this activity
        $this->logsModel->logActivity(0, "Viewed statistics for $quarter $year");

        return json_encode([
            'status' => 'success',
            'data' => $statistics,
            'message' => "Statistics for $quarter $year retrieved successfully"
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get statistics grouped by year
     * 
     * @return string JSON response with yearly statistics
     */
    public function getStatisticsByYear(): string
    {
        $statistics = $this->statsModel->getStatisticsByYear();

        return json_encode([
            'status' => !empty($statistics) ? 'success' : 'error',
            'data' => $statistics,
            'message' => empty($statistics) ? 'No yearly statistics found' : 'Yearly statistics retrieved successfully'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get statistics for the current year
     * 
     * @return string JSON response with current year statistics
     */
    public function getCurrentYearStatistics(): string
    {
        $statistics = $this->statsModel->getCurrentYearStatistics();
        $currentYear = date('Y');

        return json_encode([
            'status' => !empty($statistics) ? 'success' : 'error',
            'data' => $statistics,
            'message' => empty($statistics) ? "No statistics found for $currentYear" : "Current year ($currentYear) statistics retrieved successfully"
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get comparative statistics for multiple years
     * 
     * @param string $yearsParam Comma-separated years to compare
     * @param int $userId Optional user ID for activity logging
     * @return string JSON response with comparative statistics
     */
    public function getComparativeStatistics(string $yearsParam, int $userId = 0): string
    {
        // Validate and parse years parameter
        if (empty($yearsParam)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Years parameter must be provided as a comma-separated string'
            ], JSON_PRETTY_PRINT);
        }

        $years = array_map('intval', explode(',', $yearsParam));

        if (empty($years)) {
            return json_encode([
                'status' => 'error',
                'message' => 'At least one year must be provided'
            ], JSON_PRETTY_PRINT);
        }

        // Get comparative statistics
        $statistics = $this->statsModel->getComparativeStatistics($years);

        // Log this activity
        $this->logsModel->logActivity($userId, "Retrieved comparative statistics for years: " . implode(", ", $years));

        return json_encode([
            'status' => !empty($statistics) ? 'success' : 'error',
            'data' => $statistics,
            'message' => empty($statistics) ? 'No statistics found for the requested years' : 'Comparative statistics retrieved successfully'
        ], JSON_PRETTY_PRINT);
    }
}
