export default function ProductPrice({
    price,
    compareAtPrice = null,
    className = '',
    priceClassName = 'text-sm font-bold text-neutral-900',
    compareClassName = 'text-xs text-neutral-400 line-through',
}) {
    if (!price) {
        return null;
    }

    return (
        <div className={`flex flex-wrap items-baseline gap-2 ${className}`}>
            <span className={priceClassName}>{price}</span>
            {compareAtPrice ? <span className={compareClassName}>{compareAtPrice}</span> : null}
        </div>
    );
}
