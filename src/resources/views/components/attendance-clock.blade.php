@props(['status'])

<div class="attendance-status">
    <span class="status">{{ $status }}</span>
    <p class="attendance-date" id="attendance-date"></p>
    <p class="attendance-time" id="attendance-time"></p>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const weekdays = ["日", "月", "火", "水", "木", "金", "土"];
        const dateStr = `${now.getFullYear()}年${now.getMonth() + 1}月${now.getDate()}日(${weekdays[now.getDay()]})`;
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const timeStr = `${h}:${m}`;

        document.getElementById('attendance-date').textContent = dateStr;
        document.getElementById('attendance-time').textContent = timeStr;
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>
