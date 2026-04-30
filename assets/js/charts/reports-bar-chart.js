/**
 * Monthly Savings Overview Bar Chart
 * Displays savings data for the last 12 months
 */
(function() {
    const ctx = document.getElementById('reportsBarChart');
    if (!ctx) return;

    // Sample data - replace with dynamic data from controller
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const currentMonth = new Date().getMonth();
    const displayMonths = months.slice(Math.max(0, currentMonth - 11), currentMonth + 1);
    
    // Sample savings data (replace with actual data)
    const savingsData = [
        5000, 6500, 7200, 8100, 9300, 8900, 10200, 11500, 10800, 12100, 13400, 14200
    ].slice(Math.max(0, currentMonth - 11), currentMonth + 1);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: displayMonths,
            datasets: [{
                label: 'Monthly Savings (₱)',
                data: savingsData,
                backgroundColor: [
                    '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6',
                    '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6'
                ],
                borderColor: '#1e40af',
                borderWidth: 1,
                borderRadius: 4,
                hoverBackgroundColor: '#1e40af'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            family: "'Poppins', sans-serif"
                        },
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString('en-PH');
                        },
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                }
            }
        }
    });
})();
