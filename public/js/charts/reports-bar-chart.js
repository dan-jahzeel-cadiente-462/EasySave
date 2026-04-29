// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportsBarChart');
    if (!ctx) {
        console.error('Canvas element #reportsBarChart not found');
        return;
    }

    const labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
    const data = {
        labels: labels,
        datasets: [{
            label: 'Monthly Savings',
            data: [65, 59, 80, 81, 56, 55, 40],
            backgroundColor: [
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)',
                'rgba(59, 173, 89, 0.2)'
            ],
            borderColor: [
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)',
                'rgb(59, 173, 89)'
            ],
            borderWidth: 1
        }]
    };

    new Chart(ctx, {
        type: 'bar',
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
                        text: 'Savings Amount',
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
