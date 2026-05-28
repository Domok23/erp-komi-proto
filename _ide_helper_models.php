<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $npwp
 * @property string $type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers
 * @property-read int|null $customers_count
 * @property-read string $name_with_code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Material> $materials
 * @property-read int|null $materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesOrder> $salesOrders
 * @property-read int|null $sales_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipments
 * @property-read int|null $shipments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subcon> $subcons
 * @property-read int|null $subcons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 */
	class Company extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $project_id
 * @property int|null $design_id
 * @property string $version
 * @property string $status
 * @property numeric $material_cost
 * @property numeric $mp_cost
 * @property numeric $overhead_pct
 * @property numeric $overhead_amount
 * @property numeric $shipping_cost
 * @property numeric $profit_margin_pct
 * @property numeric $profit_margin_amount
 * @property numeric $landed_cost
 * @property numeric $selling_price
 * @property string $currency
 * @property string|null $notes
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RdDesign|null $design
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereDesignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereLandedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereMaterialCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereMpCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereOverheadAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereOverheadPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereProfitMarginAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereProfitMarginPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereSellingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereShippingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Costing whereVersion($value)
 */
	class Costing extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $address
 * @property string|null $city
 * @property string $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $npwp
 * @property string|null $payment_terms
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesOrder> $salesOrders
 * @property-read int|null $sales_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $gr_number
 * @property int|null $purchase_receipt_id
 * @property int $supplier_id
 * @property \Illuminate\Support\Carbon $receipt_date
 * @property string|null $invoice_number
 * @property string|null $notes
 * @property string|null $received_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PurchaseReceipt|null $purchaseReceipt
 * @property-read \App\Models\Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereGrNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt wherePurchaseReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt whereUpdatedAt($value)
 */
	class GoodsReceipt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $warehouse_type
 * @property int $material_id
 * @property numeric $quantity
 * @property numeric $reserved_qty
 * @property numeric $available_qty
 * @property string|null $location
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryMovement> $inventoryMovements
 * @property-read int|null $inventory_movements_count
 * @property-read \App\Models\Material $material
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereAvailableQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereReservedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereWarehouseType($value)
 */
	class Inventory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $inventory_id
 * @property int $material_id
 * @property string $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property numeric $quantity
 * @property numeric $before_qty
 * @property numeric $after_qty
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Inventory $inventory
 * @property-read \App\Models\Material $material
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereAfterQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereBeforeQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryMovement whereUpdatedAt($value)
 */
	class InventoryMovement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $invoice_number
 * @property string $type
 * @property int|null $sales_order_id
 * @property int|null $purchase_order_id
 * @property int|null $customer_id
 * @property int|null $supplier_id
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string $status
 * @property string $currency
 * @property numeric $subtotal
 * @property numeric $tax_pct
 * @property numeric $tax_amount
 * @property numeric $shipping_cost
 * @property numeric $total_amount
 * @property string|null $notes
 * @property bool $is_tax_invoice
 * @property string|null $tax_invoice_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $invoiceItems
 * @property-read int|null $invoice_items_count
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read \App\Models\SalesOrder|null $salesOrder
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIsTaxInvoice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSalesOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereShippingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $product_id
 * @property int|null $material_id
 * @property string $description
 * @property numeric $quantity
 * @property string $unit
 * @property numeric $unit_price
 * @property numeric $total_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Invoice $invoice
 * @property-read \App\Models\Material|null $material
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 */
	class InvoiceItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string $category
 * @property string $unit
 * @property numeric $stock
 * @property numeric $min_stock
 * @property numeric $price
 * @property int|null $supplier_id
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryMovement> $inventoryMovements
 * @property-read int|null $inventory_movements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $invoiceItems
 * @property-read int|null $invoice_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectBom> $projectBoms
 * @property-read int|null $project_boms_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectConsumption> $projectConsumptions
 * @property-read int|null $project_consumptions_count
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereMinStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereUpdatedAt($value)
 */
	class Material extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int|null $project_id
 * @property int|null $design_id
 * @property string $version
 * @property string $status
 * @property array<array-key, mixed>|null $materials_spec
 * @property array<array-key, mixed>|null $colors
 * @property array<array-key, mixed>|null $measurements
 * @property string|null $special_instructions
 * @property \Illuminate\Support\Carbon|null $issued_date
 * @property string|null $issued_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RdDesign|null $design
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereColors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereDesignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereIssuedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereMaterialsSpec($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereMeasurements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereSpecialInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Merchandising whereVersion($value)
 */
	class Merchandising extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property string $unit
 * @property numeric|null $weight_kg
 * @property string|null $dimensions
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvoiceItem> $invoiceItems
 * @property-read int|null $invoice_items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDimensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereWeightKg($value)
 */
	class Product extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $production_number
 * @property int $project_id
 * @property numeric $planned_qty
 * @property numeric $completed_qty
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QcInspection> $qcInspections
 * @property-read int|null $qc_inspections_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCompletedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder wherePlannedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereProductionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionOrder whereUpdatedAt($value)
 */
	class ProductionOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $status
 * @property int|null $customer_id
 * @property int|null $sales_order_id
 * @property int|null $design_id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $target_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property numeric $target_qty
 * @property numeric $produced_qty
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Costing> $costings
 * @property-read int|null $costings_count
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\RdDesign|null $design
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Merchandising> $merchandisings
 * @property-read int|null $merchandisings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionOrder> $productionOrders
 * @property-read int|null $production_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectBom> $projectBoms
 * @property-read int|null $project_boms_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectConsumption> $projectConsumptions
 * @property-read int|null $project_consumptions_count
 * @property-read \App\Models\SalesOrder|null $salesOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDesignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProducedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSalesOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTargetDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTargetQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int $material_id
 * @property numeric $quantity
 * @property numeric $wastage_pct
 * @property numeric $final_quantity
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Material $material
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereFinalQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectBom whereWastagePct($value)
 */
	class ProjectBom extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int $material_id
 * @property \Illuminate\Support\Carbon $consumption_date
 * @property numeric $quantity
 * @property string $source
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Material $material
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereConsumptionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectConsumption whereUpdatedAt($value)
 */
	class ProjectConsumption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $po_number
 * @property string $type
 * @property int|null $supplier_id
 * @property int|null $subcon_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property string $status
 * @property string $currency
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $total_amount
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseReceipt> $purchaseReceipts
 * @property-read int|null $purchase_receipts_count
 * @property-read \App\Models\Subcon|null $subcon
 * @property-read \App\Models\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder wherePoNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereSubconId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder whereUpdatedAt($value)
 */
	class PurchaseOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $receipt_number
 * @property int|null $purchase_order_id
 * @property \Illuminate\Support\Carbon $receipt_date
 * @property string $status
 * @property string|null $notes
 * @property string|null $received_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GoodsReceipt> $goodsReceipts
 * @property-read int|null $goods_receipts_count
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseReceipt whereUpdatedAt($value)
 */
	class PurchaseReceipt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $production_order_id
 * @property string $inspection_number
 * @property \Illuminate\Support\Carbon $inspection_date
 * @property int $sample_size
 * @property int $passed_qty
 * @property int $failed_qty
 * @property string $result
 * @property string|null $notes
 * @property string|null $inspector
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductionOrder $productionOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereFailedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereInspectionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereInspectionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereInspector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection wherePassedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereProductionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereSampleSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QcInspection whereUpdatedAt($value)
 */
	class QcInspection extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property string $status
 * @property string|null $sample_photo
 * @property string|null $tech_drawing
 * @property string|null $notes
 * @property numeric|null $estimated_material_cost
 * @property numeric|null $estimated_mp_cost
 * @property numeric $estimated_overhead_pct
 * @property numeric $estimated_profit_margin_pct
 * @property numeric|null $estimated_selling_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Costing> $costings
 * @property-read int|null $costings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Merchandising> $merchandisings
 * @property-read int|null $merchandisings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereEstimatedMaterialCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereEstimatedMpCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereEstimatedOverheadPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereEstimatedProfitMarginPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereEstimatedSellingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereSamplePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereTechDrawing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RdDesign whereUpdatedAt($value)
 */
	class RdDesign extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $so_number
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property string $status
 * @property string $currency
 * @property numeric $exchange_rate
 * @property numeric $subtotal
 * @property numeric $tax_pct 10% PPN + 1% JS
 * @property numeric $tax_amount
 * @property numeric $total_amount
 * @property numeric $down_payment_pct
 * @property numeric $down_payment_amount
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipments
 * @property-read int|null $shipments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDownPaymentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereDownPaymentPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereSoNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTaxPct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOrder whereUpdatedAt($value)
 */
	class SalesOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $shipment_number
 * @property int|null $sales_order_id
 * @property \Illuminate\Support\Carbon $shipment_date
 * @property string $status
 * @property string $shipping_method
 * @property string|null $container_number
 * @property string|null $bl_number
 * @property string|null $carrier
 * @property string|null $port_of_loading
 * @property string|null $port_of_discharge
 * @property \Illuminate\Support\Carbon|null $etd
 * @property \Illuminate\Support\Carbon|null $eta
 * @property int|null $total_packages
 * @property numeric|null $total_gross_weight_kg
 * @property numeric|null $total_volume_m3
 * @property numeric|null $shipping_cost_usd
 * @property string|null $notes
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SalesOrder|null $salesOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereBlNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCarrier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereContainerNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereEta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereEtd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment wherePortOfDischarge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment wherePortOfLoading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereSalesOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereShipmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereShipmentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereShippingCostUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereShippingMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTotalGrossWeightKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTotalPackages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereTotalVolumeM3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereUpdatedAt($value)
 */
	class Shipment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string $service_type
 * @property string|null $contact_person
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereServiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcon whereUpdatedAt($value)
 */
	class Subcon extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $npwp
 * @property string|null $bank_account
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GoodsReceipt> $goodsReceipts
 * @property-read int|null $goods_receipts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Material> $materials
 * @property-read int|null $materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereBankAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 */
	class Supplier extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $company_id
 * @property string $role
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

