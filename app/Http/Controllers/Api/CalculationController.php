<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calculation;
use App\Services\ExpressionParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CalculationController extends Controller
{
    public function __construct(private ExpressionParser $parser)
    {
    }

    /**
     * POST /api/calculate
     * Evaluate an expression, store it, return the result.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'expression' => ['required', 'string', 'max:500'],
        ]);

        $expression = trim($request->string('expression'));

        try {
            $result = $this->parser->evaluate($expression);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }

        if (!is_finite($result)) {
            return response()->json([
                'error' => 'Result is not a finite number (overflow or division by zero).',
            ], 422);
        }

        $calculation = Calculation::create([
            'expression' => $expression,
            'result' => $result,
        ]);

        return response()->json([
            'id' => $calculation->id,
            'expression' => $calculation->expression,
            'result' => $result,
            'created_at' => $calculation->created_at,
        ], 201);
    }

    /**
     * GET /api/calculations
     * Return calculation history, newest first.
     */
    public function index(): JsonResponse
    {
        $calculations = Calculation::latest()->get()->map(fn($c) => [
            'id' => $c->id,
            'expression' => $c->expression,
            'result' => (float) $c->result,
            'created_at' => $c->created_at,
        ]);

        return response()->json($calculations);
    }

    /**
     * DELETE /api/calculations/{id}
     * Delete a single calculation.
     */
    public function destroy(Calculation $calculation): JsonResponse
    {
        $calculation->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * DELETE /api/calculations
     * Clear all calculations.
     */
    public function destroyAll(): JsonResponse
    {
        Calculation::truncate();

        return response()->json(['message' => 'All calculations cleared']);
    }
}
