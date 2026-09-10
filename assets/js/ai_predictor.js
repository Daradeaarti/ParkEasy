/**
 * ParkEasy - AI Smart Parking Predictor & Fare Engine
 * Evaluates real-time occupancy, peak hours, and dynamic time-based fares.
 */

window.ParkEasyAI = (function () {
    // Determine peak hour coefficient (0.0 to 1.0)
    function getRushFactor(dateStr, timeStr) {
        let hour = 12;
        if (timeStr) {
            hour = parseInt(timeStr.split(':')[0], 10) || 12;
        } else {
            hour = new Date().getHours();
        }

        // Peak commuter windows: 8:30-11:30 AM and 5:00-8:30 PM
        if ((hour >= 8 && hour <= 11) || (hour >= 17 && hour <= 20)) {
            return 0.85; // High rush
        } else if ((hour >= 12 && hour <= 16) || (hour >= 21 && hour <= 22)) {
            return 0.50; // Moderate
        } else {
            return 0.20; // Low rush / Off-peak
        }
    }

    return {
        /**
         * Predict probability of getting a spot and current rush state
         */
        predictOccupancy: function (availableSlots, totalSlots, dateStr, timeStr) {
            const avail = parseInt(availableSlots, 10) || 0;
            const total = parseInt(totalSlots, 10) || 50;
            const currentRatio = avail / total;
            const rush = getRushFactor(dateStr, timeStr);

            // Likelihood calculation
            let likelihood = Math.round((currentRatio * 0.7 + (1 - rush) * 0.3) * 100);
            likelihood = Math.max(10, Math.min(99, likelihood));

            let status = 'Low Rush';
            let color = '#10b981';
            let recommendation = 'Optimal arrival time. Ample spots ready for immediate parking.';

            if (likelihood < 45 || avail <= 5) {
                status = 'Peak Hour Congestion';
                color = '#ef4444';
                recommendation = 'Heavy demand expected! Advance online booking recommended before heading out.';
            } else if (likelihood < 75 || avail <= 15) {
                status = 'Moderate Traffic';
                color = '#f59e0b';
                recommendation = 'Slots filling steadily. Arriving within 20-30 minutes advised.';
            }

            return {
                likelihood: likelihood,
                status: status,
                color: color,
                recommendation: recommendation,
                rushFactor: rush
            };
        },

        /**
         * Render AI Prediction Badge into any DOM container
         */
        renderBadge: function (containerEl, availableSlots, totalSlots) {
            if (!containerEl) return;
            const pred = this.predictOccupancy(availableSlots, totalSlots);
            containerEl.innerHTML = `
                <div style="background: rgba(255,255,255,0.95); border: 1px solid rgba(0,0,0,0.08); border-left: 4px solid ${pred.color}; border-radius: 12px; padding: 10px 14px; margin: 10px 0; font-size: 13px; text-align: left; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <span style="font-weight: 700; color: #333; display: flex; align-items: center; gap: 6px;">
                            🤖 <span>AI Spot Forecast</span>
                        </span>
                        <span style="background: ${pred.color}15; color: ${pred.color}; font-weight: 800; font-size: 11.5px; padding: 2px 8px; border-radius: 8px;">
                            ${pred.likelihood}% Spot Probability
                        </span>
                    </div>
                    <div style="color: #666; font-size: 12px; line-height: 1.4;">
                        <strong style="color: ${pred.color};">${pred.status}:</strong> ${pred.recommendation}
                    </div>
                </div>
            `;
        },

        /**
         * Dynamic duration and fee calculator
         */
        calculateDurationAndFee: function (entryTime, exitTime, vehicleType, twoWheelerPrice, fourWheelerPrice) {
            const p2w = parseFloat(twoWheelerPrice) || 15;
            const p4w = parseFloat(fourWheelerPrice) || 30;
            const hourlyRate = (vehicleType === 'two_wheeler') ? p2w : p4w;

            if (!entryTime || !exitTime) {
                return { hours: 1, total: hourlyRate, rate: hourlyRate };
            }

            const [entryH, entryM] = entryTime.split(':').map(Number);
            const [exitH, exitM] = exitTime.split(':').map(Number);

            let diffMinutes = (exitH * 60 + exitM) - (entryH * 60 + entryM);
            if (diffMinutes <= 0) {
                diffMinutes += 24 * 60; // Next day wrap
            }

            const hours = Math.max(1, Math.ceil(diffMinutes / 60));
            const total = hours * hourlyRate;

            return {
                hours: hours,
                minutes: diffMinutes,
                hourlyRate: hourlyRate,
                total: total
            };
        }
    };
})();
