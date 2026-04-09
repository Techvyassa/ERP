# Product Import CSV Template Instructions

## CSV Format

The CSV file must contain the following columns matching the Product Form fields:

| Column Name | Required | Type | Description | Example Values |
|------------|----------|------|-------------|----------------|
| product_code | No | String | **LEAVE BLANK** - Auto-generated | (empty) |
| product_name | Yes | String | Product name | "Red chilli Blend" |
| product_category | No | String | Category | AGRICULTURE, SPICES, PULSES, GRAINS |
| hsn_code | Yes | String | HSN/SAC code (must exist) | "980003", "1101" |
| pack_size | Yes | Number | Weight/Pack size | 100, 1, 500 |
| pack_uom | Yes | String | Unit code (SHORT) | KG, GM, PC, LTR, MTR |
| standard_cost | No | Number | Manufacturing cost | 80, 1000 |
| mrp | Yes | Number | Retail selling price | 100, 1500 |
| is_active | No | Boolean | Active status | true, false |

## Product Categories (from form)

- **AGRICULTURE** - Agri-Products
- **SPICES** - Spices & Powders
- **PULSES** - Pulses & Grains
- **GRAINS** - Grains

## Important Notes

1. **product_code**: **MUST BE EMPTY** - The system auto-generates codes based on category. Each row should start with a comma.

2. **product_name**: Required. Must be unique.

3. **product_category**: Optional. Use one of: AGRICULTURE, SPICES, PULSES, GRAINS (uppercase).

4. **hsn_code**: Required. Must exist in your HSN Master. Create HSN codes first if needed.

5. **pack_uom**: Required. Use ONLY short codes:
   - ✅ Correct: KG, GM, PC, LTR, MTR, BOX
   - ❌ Wrong: KG-0001, Kilogram, 98976-GG

6. **mrp**: Required. This is the retail selling price.

7. **is_active**: Optional. Defaults to `true`.

## Example CSV (Notice EMPTY first column)

```csv
product_code,product_name,product_category,hsn_code,pack_size,pack_uom,standard_cost,mrp,is_active
,Red chilli Blend,SPICES,980003,100,KG,80,100,true
,Turmeric Powder,SPICES,980001,100,KG,60,90,true
,Black Pepper,SPICES,9041100,500,GM,300,450,true
,Wheat Flour,GRAINS,1101,1,KG,40,60,true
,Basmati Rice,GRAINS,1006,5,KG,250,350,true
```

## Common UOM Codes

- **KG** - Kilogram
- **GM** - Gram  
- **PC** - Piece
- **LTR** - Liter
- **MTR** - Meter
- **BOX** - Box
- **PKT** - Packet

## Common Errors

1. **Product code not empty**: The first column MUST be empty. Start each row with a comma.
2. **Invalid UOM**: Use short codes (KG, GM) not full codes (KG-0001).
3. **Invalid HSN**: HSN code must exist in your system. Create it first.
4. **Wrong category**: Use exact values: AGRICULTURE, SPICES, PULSES, GRAINS.
5. **Duplicate names**: Each product name must be unique.

## Import Process

1. Download the CSV template
2. **Leave product_code column EMPTY** (first column after header)
3. Fill in product details matching the form fields
4. Use correct category names (AGRICULTURE, SPICES, PULSES, GRAINS)
5. Use short UOM codes (KG, GM, PC, etc.)
6. Ensure HSN codes exist in your system
7. Save as CSV
8. Import via the "Import CSV" button
9. Review results and fix any errors

## Tips

- The CSV template matches the "Add Product" form fields exactly
- Product codes are auto-generated - never fill this column
- Categories must match the form dropdown values
- Start with a small batch to test
- Download failed rows to fix and re-import
