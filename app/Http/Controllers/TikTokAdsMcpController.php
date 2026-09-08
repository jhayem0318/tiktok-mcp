<?php

namespace App\Http\Controllers;

use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokAdsAccount;
use App\Services\TikTokAdsMcpTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Throwable;

class TikTokAdsMcpController extends Controller
{
    public function __invoke(Request $request, TikTokAdsMcpTools $tools): JsonResponse|Response
    {
        $providedToken = $request->bearerToken();

        if (! $this->authenticated($providedToken)) {
            return response()->json(['error' => 'Unauthorized.'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        if ($request->isMethod('GET')) {
            return response()->json([
                'service' => 'tiktok-ads-mcp',
                'status' => 'ready',
                'transport' => 'streamable-http',
            ]);
        }

        $message = $request->json()->all();
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? null;

        if (! is_string($method)) {
            return $this->error($id, -32600, 'Invalid Request');
        }

        if ($method === 'notifications/initialized') {
            return response('', 202);
        }

        return match ($method) {
            'initialize' => $this->result($id, [
                'protocolVersion' => $this->protocolVersion($message),
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => ['name' => 'gocommerce-tiktok-ads', 'version' => '1.0.0'],
            ]),
            'ping' => $this->result($id, (object) []),
            'tools/list' => $this->result($id, ['tools' => $tools->definitions()]),
            'tools/call' => $this->callTool($id, $message, $tools),
            default => $this->error($id, -32601, 'Method not found'),
        };
    }

    private function authenticated(mixed $providedToken): bool
    {
        if (! is_string($providedToken) || $providedToken === '') {
            return false;
        }

        $token = config('services.tiktok.mcp_bearer_token');

        return is_string($token) && $token !== '' && hash_equals($token, $providedToken);
    }

    /** @param array<string, mixed> $message */
    private function protocolVersion(array $message): string
    {
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
        $requested = $params['protocolVersion'] ?? null;

        $supported = ['2025-06-18', '2025-03-26', '2024-11-05'];

        return is_string($requested) && in_array($requested, $supported, true)
            ? $requested
            : '2025-06-18';
    }

    /** @param array<string, mixed> $message */
    private function callTool(mixed $id, array $message, TikTokAdsMcpTools $tools): JsonResponse
    {
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
        $name = $params['name'] ?? null;
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (! is_string($name)) {
            return $this->error($id, -32602, 'Tool name is required.');
        }

        try {
            $accounts = TikTokAdsAccount::query()->with('authorization')->get();
            $result = $tools->call($name, $arguments, $accounts);

            return $this->result($id, [
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                ]],
                'structuredContent' => $result,
                'isError' => false,
            ]);
        } catch (InvalidArgumentException|TikTokAuthorizationException $exception) {
            return $this->toolError($id, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->toolError($id, 'The TikTok Ads read failed.');
        }
    }

    private function toolError(mixed $id, string $message): JsonResponse
    {
        return $this->result($id, [
            'content' => [['type' => 'text', 'text' => $message]],
            'isError' => true,
        ]);
    }

    /** @param array<string, mixed>|object $result */
    private function result(mixed $id, array|object $result): JsonResponse
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    private function error(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ]);
    }
}
