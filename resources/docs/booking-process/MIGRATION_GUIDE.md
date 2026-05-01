# Documentation UI Migration Guide

## Quick Start

Replace this in your HTML `<head>`:

### ❌ Before

```html
<style>
  /* 500+ lines of inline CSS */
</style>
```

### ✅ After

```html
<link rel="stylesheet" href="styles.css" />
```

## Key Changes

### 1. Info Cards/Grids

#### ❌ Before (Nested)

```html
<div class="info-grid">
  <div class="info-card">
    <h4>Title</h4>
    <ul>
      <li>Item</li>
    </ul>
  </div>
</div>
```

#### ✅ After (Simple)

```html
<div class="info-cards">
  <div class="info-card">
    <h4>Title</h4>
    <ul>
      <li>Item</li>
    </ul>
  </div>
</div>
```

**Change:** `info-grid` → `info-cards`

---

### 2. Process Flow

#### ❌ Before (Complex)

```html
<div class="payment-flow">
  <h4>Title</h4>
  <div class="flow-steps">
    <div class="flow-step">
      <strong>Step 1</strong>
      <div>Description</div>
    </div>
  </div>
</div>
```

#### ✅ After (Flexbox)

```html
<div class="process-flow">
  <div class="process-step">
    <div class="step-number">1</div>
    <h4>Step Title</h4>
    <p>Description</p>
  </div>
</div>
```

**Changes:**

- `payment-flow` → `process-flow`
- Remove `flow-steps` wrapper
- `flow-step` → `process-step`
- Add `step-number` div

---

### 3. Documentation Cards

#### ❌ Before

```html
<div class="documentation-grid">
  <div class="doc-card">
    <h3>Title</h3>
    <p>Description</p>
    <ul class="feature-list">
      <li>Feature</li>
    </ul>
  </div>
</div>
```

#### ✅ After (Same!)

```html
<div class="documentation-grid">
  <div class="doc-card">
    <h3>Title</h3>
    <p>Description</p>
    <ul class="features">
      <li>Feature</li>
    </ul>
    <a href="#" class="doc-link">View Docs →</a>
  </div>
</div>
```

**Changes:**

- `feature-list` → `features`
- Add `doc-link` at bottom

---

### 4. Highlight Boxes

#### ✅ No Change Needed

```html
<div class="highlight">
  <h4>Title</h4>
  <p>Content</p>
</div>
```

---

### 5. Code Blocks

#### ❌ Before

```html
<div class="code-block">
  <pre>code here</pre>
</div>
```

#### ✅ After (Add data attribute)

```html
<div class="code-block" data-lang="PHP">
  <pre>code here</pre>
</div>
```

**Change:** Add `data-lang` attribute for language badge

---

## Search & Replace Guide

Use your editor's find & replace feature:

| Find                   | Replace                                     |
| ---------------------- | ------------------------------------------- |
| `class="info-grid"`    | `class="info-cards"`                        |
| `class="payment-flow"` | `class="process-flow"`                      |
| `class="flow-steps"`   | `class="process-flow"` (remove wrapper)     |
| `class="flow-step"`    | `class="process-step"`                      |
| `class="feature-list"` | `class="features"`                          |
| `<style>`              | `<link rel="stylesheet" href="styles.css">` |

## File Updates

### Priority 1 - Update These Files First:

- [ ] index.html
- [ ] customer-verification.html
- [ ] receipt-creation.html

### Priority 2 - Then Update:

- [ ] ar-invoice-lifecycle.html
- [ ] ap-invoice-lifecycle.html
- [ ] payment-mapping.html

### Priority 3 - Finally:

- [ ] main-lead-sendupdate.html
- [ ] embedded-product-booking.html

## Testing Checklist

After migration:

- [ ] Page loads without style issues
- [ ] Responsive design works (resize browser)
- [ ] All cards display correctly
- [ ] Process steps show arrows
- [ ] Code blocks show language badges
- [ ] Links work properly
- [ ] Hover effects work
- [ ] Mobile view looks good (< 768px)
- [ ] Tables scroll on mobile
- [ ] Print preview looks clean

## Common Issues

### Issue 1: Cards Not Aligned

**Problem:** Mixed old and new grid classes  
**Solution:** Ensure consistent use of new classes

### Issue 2: Process Steps Stack Weirdly

**Problem:** Old wrapper div still present  
**Solution:** Remove `flow-steps` div, use `process-flow` directly

### Issue 3: Arrows Missing

**Problem:** Old class names  
**Solution:** Use `process-step` class (arrows are CSS ::after)

### Issue 4: Colors Look Off

**Problem:** Inline styles override CSS file  
**Solution:** Remove inline `<style>` block completely

## Benefits Summary

| Aspect           | Before           | After             |
| ---------------- | ---------------- | ----------------- |
| **CSS Lines**    | ~500 per file    | 0 (external)      |
| **Grid Nesting** | 3-4 levels       | 1-2 levels        |
| **Maintenance**  | Update each file | Update 1 CSS file |
| **File Size**    | Larger           | Smaller           |
| **Load Time**    | Slower           | Faster            |
| **Consistency**  | Varies           | Uniform           |

## Need Help?

1. View `sample-improved.html` for working example
2. Check `STYLE_GUIDE.md` for full documentation
3. Review `styles.css` for all available classes
4. Test in `sample-improved.html` before migrating

## Quick Migration Script

For command-line enthusiasts:

```bash
# Backup files first
cp file.html file.html.backup

# Replace inline style with link (manual step recommended)
# Update class names
sed -i 's/class="info-grid"/class="info-cards"/g' *.html
sed -i 's/class="payment-flow"/class="process-flow"/g' *.html
sed -i 's/class="feature-list"/class="features"/g' *.html

# Test each file after changes
```

**⚠️ Warning:** Always backup before running automated scripts!

## Support

Questions? Check:

- `styles.css` - All CSS definitions
- `STYLE_GUIDE.md` - Detailed guide
- `sample-improved.html` - Working example
