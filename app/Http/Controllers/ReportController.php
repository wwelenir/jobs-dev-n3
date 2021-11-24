<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Report;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

/**
 * Class ReportController
 * @package App\Http\Controllers
 */
class ReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listReports(Request $request)
    {
        try {
            $apiUrl = 'https://api.spaceflightnewsapi.net/v3/';

            $guzzle = new Client([
                'base_uri' => $apiUrl
            ]);
            $rawResult = json_decode($guzzle->get('reports')->getBody(), true);

            $filter = $request->get('filter');

            $result = [];

            for ($x = 0; $x < sizeof($rawResult); $x++) {
                $reports = Report::where('external_id', $rawResult[$x]['id'])->get();
                if (count($reports) == 0) {
                    Report::create([
                        'external_id' => $rawResult[$x]['id'],
                        'title' => $rawResult[$x]['title'],
                        'url' => $rawResult[$x]['url'],
                        'summary' => $rawResult[$x]['summary']
                    ]);
                }
                $posTitle = !empty($filter) ? strpos($rawResult[$x]['title'], $filter) : FALSE;
                $posSummary = !empty($filter) ? strpos($rawResult[$x]['summary'], $filter) : FALSE;
                if ($posTitle === FALSE && $posSummary === FALSE) {
                    continue;
                }

                $result[] = $rawResult[$x];
            }

            return response()->json(['data' => $result, "status" => "ok"]);
        } catch (Exception $e) {
            return response()->json(["status" => "error"]);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function createReport(Request $request)
    {
        $report = Report::create([
            'external_id' => $request->post('external_id'),
            'title' => $request->post('title'),
            'url' => $request->post('url'),
            'summary' => $request->post('summary')
        ]);

        return (new ReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * TODO: Implement it
     *
     * @param $reportId
     */
    public function deleteReport($reportId)
    {
        $report = Report::find($reportId);
        $report->delete();
        return response()->json(["status" => "ok"]);
    }
}
