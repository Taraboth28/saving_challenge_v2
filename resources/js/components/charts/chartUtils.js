/**
 * Validated categorical series colors (light surface). Assign in this fixed order —
 * never cycle; fold extra series into "Other" instead.
 */
export const SERIES_COLORS = ['var(--color-series-1)', 'var(--color-series-2)'];

/** "Nice" axis ticks covering [min, max], always including zero. */
export function niceTicks(min, max, count = 4) {
    const low = Math.min(0, min);
    const high = Math.max(0, max);

    if (low === high) {
        return [0, 1];
    }

    const rawStep = (high - low) / count;
    const magnitude = 10 ** Math.floor(Math.log10(rawStep));
    const step = [1, 2, 2.5, 5, 10].map((factor) => factor * magnitude).find((candidate) => candidate >= rawStep);

    const ticks = [];

    for (let tick = Math.floor(low / step) * step; tick <= Math.ceil(high / step) * step + step / 2; tick += step) {
        ticks.push(Number(tick.toFixed(10)));
    }

    return ticks;
}
