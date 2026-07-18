import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';

const STORAGE_KEY = 'mummish_cart_v2';
const LEGACY_STORAGE_KEY = 'mummish_cart_v1';
const ACTIVITY_KEY = 'mummish_cart_activity_v1';
const INACTIVE_MS = 30 * 60 * 1000;
const EXPIRY_CHECK_MS = 60 * 1000;
const ABSOLUTE_MAX_QTY = 99;

/** Parse "GHS 34.00" → 34 */
export function parsePriceAmount(priceLabel) {
    const m = String(priceLabel ?? '').match(/[\d]+(?:[.,]\d{2})?/);
    if (!m) return 0;
    return parseFloat(m[0].replace(',', '.'));
}

function normalizeMaxStock(stockQuantity, fallback = ABSOLUTE_MAX_QTY) {
    const stock = Number(stockQuantity);
    if (!Number.isFinite(stock)) {
        return fallback;
    }
    if (stock < 1) {
        return 0;
    }

    return Math.min(ABSOLUTE_MAX_QTY, Math.floor(stock));
}

function totalQtyForProduct(lines, productId) {
    return lines
        .filter((line) => line.productId === productId)
        .reduce((sum, line) => sum + (line.qty || 1), 0);
}

function clampLinesToStock(lines) {
    const productIds = [...new Set(lines.map((line) => line.productId))];
    let result = [...lines];

    for (const productId of productIds) {
        const productLines = result.filter((line) => line.productId === productId);
        const maxStock = normalizeMaxStock(productLines[0]?.maxStock ?? ABSOLUTE_MAX_QTY);

        if (maxStock < 1) {
            result = result.filter((line) => line.productId !== productId);
            continue;
        }

        let total = productLines.reduce((sum, line) => sum + line.qty, 0);

        if (total <= maxStock) {
            result = result.map((line) =>
                line.productId === productId ? { ...line, maxStock } : line
            );
            continue;
        }

        let excess = total - maxStock;
        result = result.map((line) => {
            if (line.productId !== productId || excess <= 0) {
                return line.productId === productId ? { ...line, maxStock } : line;
            }

            const reducible = Math.max(0, line.qty - 1);
            const reduceBy = Math.min(excess, reducible);
            excess -= reduceBy;

            return {
                ...line,
                maxStock,
                qty: line.qty - reduceBy,
            };
        });
    }

    return result;
}

function readStoredActivity() {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const raw = localStorage.getItem(ACTIVITY_KEY);
        if (raw == null) return null;
        const n = parseInt(raw, 10);
        return Number.isFinite(n) ? n : null;
    } catch {
        return null;
    }
}

function writeActivity(ts) {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        localStorage.setItem(ACTIVITY_KEY, String(ts));
    } catch {
        /* ignore */
    }
}

function migrateLegacyCart() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(LEGACY_STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed) || parsed.length === 0) {
            localStorage.removeItem(LEGACY_STORAGE_KEY);
            return null;
        }

        localStorage.removeItem(LEGACY_STORAGE_KEY);

        return {
            vendor: null,
            lines: clampLinesToStock(parsed),
        };
    } catch {
        localStorage.removeItem(LEGACY_STORAGE_KEY);
        return null;
    }
}

function loadCartRaw() {
    if (typeof window === 'undefined') {
        return { vendor: null, lines: [] };
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && Array.isArray(parsed.lines)) {
                return {
                    vendor: parsed.vendor ?? null,
                    lines: clampLinesToStock(parsed.lines),
                };
            }
        }
    } catch {
        /* fall through to migration */
    }

    const migrated = migrateLegacyCart();
    if (migrated) {
        return migrated;
    }

    return { vendor: null, lines: [] };
}

function saveCart(vendor, lines) {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ vendor, lines }));
    } catch {
        /* ignore quota */
    }
}

function buildLine(item) {
    return {
        lineId: `${item.productId}-${item.attributes}-${Date.now()}`,
        productId: item.productId,
        name: item.name,
        image: item.image,
        priceLabel: item.priceLabel,
        priceAmount: parsePriceAmount(item.priceLabel),
        attributes: item.attributes ?? '',
        maxStock: normalizeMaxStock(item.stockQuantity),
        qty: 1,
    };
}

function insertLine(prev, item) {
    const maxStock = normalizeMaxStock(item.stockQuantity);
    if (maxStock < 1) {
        return { lines: prev, added: false, reason: 'stock' };
    }

    const inCart = totalQtyForProduct(prev, item.productId);
    if (inCart >= maxStock) {
        return { lines: prev, added: false, reason: 'stock' };
    }

    const attributes = item.attributes ?? '';
    const index = prev.findIndex((line) => line.productId === item.productId && line.attributes === attributes);

    if (index >= 0) {
        const next = [...prev];
        next[index] = {
            ...next[index],
            maxStock,
            qty: Math.min(maxStock - inCart + next[index].qty, next[index].qty + 1),
        };

        return { lines: clampLinesToStock(next), added: true };
    }

    return {
        lines: clampLinesToStock([...prev, buildLine({ ...item, attributes })]),
        added: true,
    };
}

/**
 * @returns {{ vendor: object|null, lines: array, activityAt: number }}
 */
function loadInitialState() {
    if (typeof window === 'undefined') {
        return { vendor: null, lines: [], activityAt: Date.now() };
    }

    const { vendor, lines } = loadCartRaw();
    const now = Date.now();
    let activityAt = readStoredActivity();

    if (lines.length > 0) {
        if (activityAt === null) {
            activityAt = now;
            writeActivity(activityAt);
        } else if (now - activityAt > INACTIVE_MS) {
            saveCart(null, []);
            activityAt = now;
            writeActivity(activityAt);
            return { vendor: null, lines: [], activityAt };
        }
    } else {
        activityAt = activityAt ?? now;
    }

    return { vendor, lines, activityAt };
}

const CartContext = createContext(null);

export function CartProvider({ children }) {
    const initial = loadInitialState();
    const lastActivityRef = useRef(initial.activityAt);
    const [vendor, setVendor] = useState(initial.vendor);
    const [lines, setLines] = useState(initial.lines);
    const [isOpen, setIsOpen] = useState(false);
    const [vendorConflict, setVendorConflict] = useState(null);

    const touchActivity = useCallback(() => {
        const t = Date.now();
        lastActivityRef.current = t;
        writeActivity(t);
    }, []);

    const expireIfIdle = useCallback(() => {
        if (Date.now() - lastActivityRef.current <= INACTIVE_MS) {
            return;
        }
        setVendor(null);
        setLines((prev) => {
            if (prev.length === 0) {
                return prev;
            }
            const t = Date.now();
            lastActivityRef.current = t;
            writeActivity(t);
            saveCart(null, []);
            return [];
        });
    }, []);

    useEffect(() => {
        saveCart(vendor, lines);
    }, [vendor, lines]);

    useEffect(() => {
        const id = window.setInterval(expireIfIdle, EXPIRY_CHECK_MS);
        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                expireIfIdle();
            }
        };
        document.addEventListener('visibilitychange', onVisibility);
        return () => {
            window.clearInterval(id);
            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [expireIfIdle]);

    const openCart = useCallback(() => {
        touchActivity();
        setIsOpen(true);
    }, [touchActivity]);

    const closeCart = useCallback(() => setIsOpen(false), []);

    const syncStockLevels = useCallback((stockByProductId) => {
        setLines((prev) => {
            const next = prev.map((line) => {
                const stock = stockByProductId[line.productId];
                if (stock === undefined) {
                    return line;
                }

                return {
                    ...line,
                    maxStock: normalizeMaxStock(stock),
                };
            });

            return clampLinesToStock(next);
        });
    }, []);

    const getRemainingStock = useCallback(
        (productId, maxStock = ABSOLUTE_MAX_QTY) => {
            const limit = normalizeMaxStock(maxStock);
            return Math.max(0, limit - totalQtyForProduct(lines, productId));
        },
        [lines]
    );

    const addItem = useCallback(
        (item) => {
            touchActivity();

            const vendorUserId = Number(item.vendorUserId);
            if (!Number.isFinite(vendorUserId) || vendorUserId < 1) {
                return { ok: false, reason: 'invalid_vendor' };
            }

            const nextVendor = {
                userId: vendorUserId,
                name: item.vendorName || 'Seller',
                slug: item.vendorSlug ?? null,
            };

            if (lines.length > 0 && vendor && vendor.userId !== nextVendor.userId) {
                setVendorConflict({
                    pendingItem: item,
                    currentVendor: vendor,
                    newVendor: nextVendor,
                });

                return { ok: false, reason: 'vendor_conflict' };
            }

            const result = insertLine(lines, item);

            if (!result.added) {
                return { ok: false, reason: 'stock' };
            }

            setVendor(nextVendor);
            setLines(result.lines);
            setIsOpen(true);

            return { ok: true };
        },
        [touchActivity, lines, vendor]
    );

    const confirmVendorSwitch = useCallback(() => {
        if (!vendorConflict?.pendingItem) {
            setVendorConflict(null);
            return;
        }

        touchActivity();
        const item = vendorConflict.pendingItem;
        setVendorConflict(null);

        const nextVendor = {
            userId: item.vendorUserId,
            name: item.vendorName || 'Seller',
            slug: item.vendorSlug ?? null,
        };
        const result = insertLine([], item);

        setVendor(nextVendor);
        setLines(result.lines);

        if (result.added) {
            setIsOpen(true);
        }
    }, [vendorConflict, touchActivity]);

    const cancelVendorSwitch = useCallback(() => {
        setVendorConflict(null);
    }, []);

    const setQty = useCallback(
        (lineId, qty) => {
            touchActivity();
            setLines((prev) => {
                const line = prev.find((l) => l.lineId === lineId);
                if (!line) {
                    return prev;
                }

                const maxStock = normalizeMaxStock(line.maxStock);
                const otherQty = totalQtyForProduct(prev, line.productId) - line.qty;
                const allowed = Math.max(1, Math.min(maxStock - otherQty, Number(qty) || 1));

                return prev.map((l) => (l.lineId === lineId ? { ...l, qty: allowed } : l));
            });
        },
        [touchActivity]
    );

    const increment = useCallback(
        (lineId) => {
            touchActivity();
            setLines((prev) => {
                const line = prev.find((l) => l.lineId === lineId);
                if (!line) {
                    return prev;
                }

                const maxStock = normalizeMaxStock(line.maxStock);
                const inCart = totalQtyForProduct(prev, line.productId);
                if (inCart >= maxStock) {
                    return prev;
                }

                return prev.map((l) =>
                    l.lineId === lineId ? { ...l, qty: Math.min(maxStock - inCart + l.qty, l.qty + 1) } : l
                );
            });
        },
        [touchActivity]
    );

    const decrement = useCallback(
        (lineId) => {
            touchActivity();
            setLines((prev) =>
                prev.map((l) => (l.lineId === lineId ? { ...l, qty: Math.max(1, l.qty - 1) } : l))
            );
        },
        [touchActivity]
    );

    const removeLine = useCallback(
        (lineId) => {
            touchActivity();
            setLines((prev) => {
                const next = prev.filter((l) => l.lineId !== lineId);
                if (next.length === 0) {
                    setVendor(null);
                }
                return next;
            });
        },
        [touchActivity]
    );

    const clearCart = useCallback(() => {
        touchActivity();
        setVendor(null);
        setLines([]);
    }, [touchActivity]);

    const subtotal = useMemo(
        () => lines.reduce((sum, l) => sum + (Number(l.priceAmount) || 0) * (l.qty || 1), 0),
        [lines]
    );

    const count = useMemo(() => lines.reduce((n, l) => n + (l.qty || 1), 0), [lines]);

    const value = useMemo(
        () => ({
            vendor,
            lines,
            isOpen,
            vendorConflict,
            openCart,
            closeCart,
            addItem,
            confirmVendorSwitch,
            cancelVendorSwitch,
            setQty,
            increment,
            decrement,
            removeLine,
            clearCart,
            syncStockLevels,
            getRemainingStock,
            subtotal,
            count,
        }),
        [
            vendor,
            lines,
            isOpen,
            vendorConflict,
            openCart,
            closeCart,
            addItem,
            confirmVendorSwitch,
            cancelVendorSwitch,
            setQty,
            increment,
            decrement,
            removeLine,
            clearCart,
            syncStockLevels,
            getRemainingStock,
            subtotal,
            count,
        ]
    );

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
    const ctx = useContext(CartContext);
    if (!ctx) {
        throw new Error('useCart must be used within CartProvider');
    }
    return ctx;
}
