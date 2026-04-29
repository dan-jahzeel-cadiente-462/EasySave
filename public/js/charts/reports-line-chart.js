document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('reportsLineChart');
    if (!ctx) {
        console.error('Canvas element #reportsLineChart not found');
        return;
    }

    const labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
    const data = {
        labels: labels,
        datasets: [{
            label: 'Products by Highest Value',
            data: [120, 190, 150, 250, 220, 300, 280],
            fill: true,
            backgroundColor: 'rgba(59, 173, 89, 0.1)',
            borderColor: 'rgb(59, 173, 89)',
            tension: 0.4,
            pointBackgroundColor: 'rgb(59, 173, 89)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(59, 173, 89)'
        }]
    };

    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    },
                    title: {
                        display: true,
                        text: 'Product Value',
                        font: {
                            family: 'Poppins',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    },
                    title: {
                        display: true,
                        text: 'Months',
                        font: {
                            family: 'Poppins',
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    titleFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 12
                    }
                }
            }
        }
    });
});
