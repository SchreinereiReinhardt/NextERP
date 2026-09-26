<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;

/** Separates external attendance (HR) from Betrio project allocation. */
final class WorkingTimeService {
    public function __construct(private IDBConnection $db, private NullWorkingTimeProvider $provider) {}

    /** @return array<string,mixed> */
    public function daySummary(string $userId, string $date): array {
        $attendance = $this->provider->attendance($userId, $date);

        $qb = $this->db->getQueryBuilder();
        $qb->select('e.project_id', 'e.activity', 'e.hours', 'p.project_no', 'p.title')
            ->from('re_erp_workday_entries', 'e')
            ->innerJoin('e', 're_erp_workdays', 'w', $qb->expr()->eq('w.id', 'e.workday_id'))
            ->leftJoin('e', 're_erp_projects', 'p', $qb->expr()->eq('p.id', 'e.project_id'))
            ->where($qb->expr()->eq('w.user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('w.work_date', $qb->createNamedParameter($date)))
            ->orderBy('e.id', 'ASC');

        $allocated = 0.0;
        $travelHours = 0.0;
        $projects = [];
        foreach ($qb->executeQuery()->fetchAll() as $row) {
            $hours = round((float)($row['hours'] ?? 0), 2);
            $allocated += $hours;
            $activity = trim((string)($row['activity'] ?? ''));
            $activityLower = mb_strtolower($activity);
            $isTravel = str_contains($activityLower, 'anfahrt') || str_contains($activityLower, 'fahrt') || str_contains($activityLower, 'travel');
            if ($isTravel) $travelHours += $hours;

            $projectId = (int)($row['project_id'] ?? 0);
            $key = (string)$projectId;
            if (!isset($projects[$key])) {
                $projects[$key] = [
                    'projectId' => $projectId,
                    'projectNo' => (string)($row['project_no'] ?? ''),
                    'projectName' => (string)($row['title'] ?? ''),
                    'hours' => 0.0,
                    'travelHours' => 0.0,
                    'activities' => [],
                ];
            }
            $projects[$key]['hours'] = round((float)$projects[$key]['hours'] + $hours, 2);
            if ($isTravel) $projects[$key]['travelHours'] = round((float)$projects[$key]['travelHours'] + $hours, 2);
            if ($activity !== '') {
                $aKey = $activity;
                $projects[$key]['activities'][$aKey] = round((float)($projects[$key]['activities'][$aKey] ?? 0) + $hours, 2);
            }
        }

        $allocations = [];
        foreach ($projects as $project) {
            $activities = [];
            foreach ($project['activities'] as $activity => $hours) {
                $activities[] = ['activity' => $activity, 'hours' => $hours];
            }
            $project['activities'] = $activities;
            $allocations[] = $project;
        }

        $allocated = round($allocated, 2);
        $travelHours = round($travelHours, 2);
        $projectHours = round($allocated - $travelHours, 2);
        $attendanceHours = $attendance['hours'] === null ? null : round((float)$attendance['hours'], 2);

        return [
            'date' => $date,
            'userId' => $userId,
            'provider' => (string)$attendance['source'],
            'externalAvailable' => (bool)$attendance['available'],
            'attendanceHours' => $attendanceHours,
            'allocatedHours' => $allocated,
            'projectHours' => $projectHours,
            'travelHours' => $travelHours,
            'remainingHours' => $attendanceHours === null ? null : round($attendanceHours - $allocated, 2),
            'allocations' => $allocations,
            'note' => (string)$attendance['note'],
        ];
    }
}
