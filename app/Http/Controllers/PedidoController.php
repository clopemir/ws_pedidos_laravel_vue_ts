<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pedido;
use Illuminate\Http\Request;
use App\Models\PedidoProducto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index() {
        $pedidos = Pedido::with(['pedidoProducto.producto' => function ($query) {
            $query->select('id', 'nombre');
        }])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return Inertia::render('Pedidos/Index', [
            'pedidos' => $pedidos,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_cliente' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'm_pago' => 'required|string|max:50',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|exists:productos,id', // Verifica que el producto exista
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction(); // Iniciar transacción para asegurar la atomicidad

        try {
            $pedidoTotal = 0;


            $pedido = Pedido::create([
                'nombre_cliente' => $validatedData['nombre_cliente'],
                'direccion' => $validatedData['direccion'],
                'telefono' => $validatedData['telefono'],
                'm_pago' => $validatedData['m_pago'],
                'total' => 0,
            ]);

            foreach ($validatedData['productos'] as $productoData) {

                $subtotal = $productoData['cantidad'] * $productoData['precio_unitario'];
                $pedidoTotal += $subtotal;

                PedidoProducto::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);
            }

            // Actualizar el total del pedido
            $pedido->total = $pedidoTotal;
            $pedido->save();

            DB::commit(); // Confirmar la transacción

            return response()->json([
                'message' => 'Pedido creado exitosamente.',
                'pedido_id' => $pedido->id,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack(); // Revertir la transacción en caso de error
            Log::error("Error al crear pedido: " . $e->getMessage()); // Loguear el error
            return response()->json([
                'message' => 'Error al crear el pedido.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reporteCantidades()
    {
        $reporte = DB::table('pedido_productos')
            ->join('productos', 'pedido_productos.producto_id', '=', 'productos.id')
            ->select(
                'pedido_productos.producto_id',
                'productos.nombre',
                DB::raw('SUM(pedido_productos.cantidad) as total_cantidad')
            )
            ->groupBy('pedido_productos.producto_id', 'productos.nombre')
            ->orderBy('total_cantidad', 'desc')
            ->get();

        if ($reporte->isEmpty()) {
            return response()->json(['message' => 'No hay datos de productos pedidos para mostrar.'], 200);
        }

        return response()->json($reporte);
    }
}
