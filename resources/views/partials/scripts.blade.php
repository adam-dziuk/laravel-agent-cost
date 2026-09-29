<script>
    (() => {
        const tooltip = document.querySelector('[data-tooltip]');
        const [valueText, labelText] = tooltip.children;

        const showTooltip = (anchor, value, label) => {
            valueText.textContent = value;
            labelText.textContent = label;
            tooltip.hidden = false;

            const box = anchor.getBoundingClientRect();
            const halfWidth = tooltip.offsetWidth / 2;

            tooltip.style.left = `${Math.min(Math.max(box.left + box.width / 2, halfWidth + 8), window.innerWidth - halfWidth - 8)}px`;
            tooltip.style.top = `${Math.max(box.top, tooltip.offsetHeight + 16)}px`;
        };

        const hideTooltip = () => {
            tooltip.hidden = true;
        };

        // Column chart: every column is a hover target, and the chart itself
        // is a single tab stop whose columns are stepped through with arrows.
        document.querySelectorAll('[data-chart]').forEach((chart) => {
            const columns = [...chart.querySelectorAll('.column')];
            let active = -1;

            const activate = (index) => {
                columns[active]?.classList.remove('is-active');
                active = index;
                columns[active].classList.add('is-active');
                showTooltip(columns[active].firstElementChild, columns[active].dataset.value, columns[active].dataset.label);
            };

            const deactivate = () => {
                columns[active]?.classList.remove('is-active');
                hideTooltip();
            };

            columns.forEach((column, index) => column.addEventListener('pointerenter', () => activate(index)));

            chart.addEventListener('pointerleave', () => chart.matches(':focus-visible') ? activate(active) : deactivate());
            chart.addEventListener('focus', () => activate(active < 0 ? columns.length - 1 : active));
            chart.addEventListener('blur', deactivate);
            chart.addEventListener('keydown', (event) => {
                const next = { ArrowLeft: active - 1, ArrowRight: active + 1, Home: 0, End: columns.length - 1 }[event.key];

                if (next === undefined) {
                    return;
                }

                event.preventDefault();
                activate(Math.min(Math.max(next, 0), columns.length - 1));
            });
        });

        // Sparklines: the pointer snaps to the nearest bucket.
        const labels = JSON.parse(document.querySelector('[data-labels]')?.dataset.labels ?? '[]');

        document.querySelectorAll('[data-sparkline]').forEach((sparkline) => {
            const points = JSON.parse(sparkline.dataset.points);
            const values = JSON.parse(sparkline.dataset.values);
            const dot = sparkline.querySelector('.sparkline-dot');

            sparkline.addEventListener('pointermove', (event) => {
                const box = sparkline.getBoundingClientRect();
                const x = (event.clientX - box.left) / box.width * sparkline.viewBox.baseVal.width;
                const index = points.reduce((nearest, [px], i) => Math.abs(px - x) < Math.abs(points[nearest][0] - x) ? i : nearest, 0);

                dot.setAttribute('cx', points[index][0]);
                dot.setAttribute('cy', points[index][1]);
                sparkline.classList.add('is-active');
                showTooltip(dot, values[index], labels[index]);
            });

            sparkline.addEventListener('pointerleave', () => {
                sparkline.classList.remove('is-active');
                hideTooltip();
            });
        });
    })();
</script>
