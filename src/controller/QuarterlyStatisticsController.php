<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\QuarterlyStatistics;
use App\Model\ActivityLogs;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class QuarterlyStatisticsController
{
    private QuarterlyStatistics $quarterlyStatsModel;
    private ActivityLogs $activityLogs;

    public function __construct(QuarterlyStatistics $quarterlyStatsModel, ActivityLogs $activityLogs)
    {
        $this->quarterlyStatsModel = $quarterlyStatsModel;
        $this->activityLogs = $activityLogs;
    }

    /**
     * Get all quarterly statistics
     * 
     * @param Request $request The request object
     * @param Response $response The response object
     * @return Response The response with all statistics
     */
    public function getAllStatistics(Request $request, Response $response): Response
    {
        try {
            $statistics = $this->quarterlyStatsModel->getAllStatistics();

            $responseData = [
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Quarterly statistics retrieved successfully'
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get statistics for a specific period (quarter and year)
     * 
     * @param Request $request The request object
     * @param Response $response The response object
     * @param array $args Route arguments
     * @return Response The response with period statistics
     */
    public function getStatisticsByPeriod(Request $request, Response $response, array $args): Response
    {
        try {
            if (!isset($args['quarter']) || !isset($args['year'])) {
                $responseData = [
                    'status' => 'error',
                    'message' => 'Quarter and year must be provided'
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $quarter = $args['quarter'];
            $year = (int)$args['year'];

            if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
                $responseData = [
                    'status' => 'error',
                    'message' => 'Invalid quarter format. Must be Q1, Q2, Q3, or Q4'
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $statistics = $this->quarterlyStatsModel->getStatisticsByPeriod($quarter, $year);

            if ($statistics === null) {
                $responseData = [
                    'status' => 'error',
                    'message' => "No statistics found for $quarter $year"
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $responseData = [
                'status' => 'success',
                'data' => $statistics,
                'message' => "Statistics for $quarter $year retrieved successfully"
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get statistics grouped by year
     * 
     * @param Request $request The request object
     * @param Response $response The response object
     * @return Response The response with yearly statistics
     */
    public function getStatisticsByYear(Request $request, Response $response): Response
    {
        try {
            $statistics = $this->quarterlyStatsModel->getStatisticsByYear();

            $responseData = [
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Yearly statistics retrieved successfully'
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get statistics for the current year
     * 
     * @param Request $request The request object
     * @param Response $response The response object
     * @return Response The response with current year statistics
     */
    public function getCurrentYearStatistics(Request $request, Response $response): Response
    {
        try {
            $statistics = $this->quarterlyStatsModel->getCurrentYearStatistics();

            $responseData = [
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Current year statistics retrieved successfully'
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get comparative statistics for multiple years
     * 
     * @param Request $request The request object
     * @param Response $response The response object
     * @return Response The response with comparative statistics
     */
    public function getComparativeStatistics(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();

            if (!isset($params['years']) || !is_string($params['years'])) {
                $responseData = [
                    'status' => 'error',
                    'message' => 'Years parameter must be provided as a comma-separated string'
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $years = array_map('intval', explode(',', $params['years']));

            if (empty($years)) {
                $responseData = [
                    'status' => 'error',
                    'message' => 'At least one year must be provided'
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $statistics = $this->quarterlyStatsModel->getComparativeStatistics($years);

            $responseData = [
                'status' => 'success',
                'data' => $statistics,
                'message' => 'Comparative statistics retrieved successfully'
            ];

            // Log this activity
            $userId = isset($request->getAttribute('user')['user_id']) ? $request->getAttribute('user')['user_id'] : 0;
            $this->activityLogs->logActivity($userId, "Retrieved comparative statistics for years: " . implode(", ", $years));

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
