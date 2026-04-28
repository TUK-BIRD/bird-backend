# BLE Scan Dashboard Compare Prompt Spec

## 목적

room 단위 BLE scan 이벤트 대시보드에서 현재 조회 구간과 지난주 동일 시간 구간을 비교해서 보여준다.

예시:

- 현재 조회 구간: `2026-04-28 14:07 ~ 14:43`
- 비교 구간: `2026-04-21 14:07 ~ 14:43`

즉, 분 단위까지 같은 구간을 정확히 7일 전으로 이동한 데이터를 비교한다.

## 백엔드 엔드포인트

```text
GET /api/spaces/{spaceId}/rooms/{roomId}/ble_scan_events/dashboard
```

쿼리 파라미터:

- `since`: ISO8601 datetime
- `until`: ISO8601 datetime
- `limit`: number, optional

예시:

```text
/api/spaces/3/rooms/12/ble_scan_events/dashboard?since=2026-04-28T14:07:00+09:00&until=2026-04-28T14:43:00+09:00&limit=20
```

## 비교 규칙

- 현재 구간의 `since`, `until`을 그대로 사용한다.
- 비교 구간은 각각 `7일 전`으로 계산한다.
- 분과 초를 그대로 유지한다.

예시:

- current `since`: `2026-04-28T14:07:00+09:00`
- current `until`: `2026-04-28T14:43:00+09:00`
- previous week `since`: `2026-04-21T14:07:00+09:00`
- previous week `until`: `2026-04-21T14:43:00+09:00`

## 응답 예시

```json
{
  "space": {
    "id": 3,
    "name": "Bird Space"
  },
  "room": {
    "id": 12,
    "name": "Room A"
  },
  "timespan": {
    "since": "2026-04-28T14:07:00+09:00",
    "until": "2026-04-28T14:43:00+09:00",
    "limit": 20
  },
  "stats": {
    "totalEvents": 12,
    "uniqueDevices": 8,
    "averageRssi": -57.4,
    "latestEventScannedAt": "2026-04-28T14:40:12+09:00",
    "anchorBreakdown": [
      {
        "anchorId": 101,
        "anchorUid": "aa:bb:cc:dd:ee:01",
        "label": "Anchor A",
        "eventCount": 7,
        "averageRssi": -55.8,
        "lastScannedAt": "2026-04-28T14:40:12+09:00"
      }
    ],
    "timeSeries": [
      {
        "bucket": "2026-04-28T14:00:00+09:00",
        "eventCount": 8,
        "uniqueDeviceCount": 8,
        "averageRssi": -57.4
      }
    ],
    "windowMinutes": 36
  },
  "comparison": {
    "previousWeek": {
      "timespan": {
        "since": "2026-04-21T14:07:00+09:00",
        "until": "2026-04-21T14:43:00+09:00"
      },
      "stats": {
        "totalEvents": 9,
        "uniqueDevices": 6,
        "averageRssi": -61.2,
        "anchorBreakdown": [
          {
            "anchorId": 101,
            "anchorUid": "aa:bb:cc:dd:ee:01",
            "label": "Anchor A",
            "eventCount": 5,
            "averageRssi": -60.1,
            "lastScannedAt": "2026-04-21T14:38:10+09:00"
          }
        ],
        "timeSeries": [
          {
            "bucket": "2026-04-21T14:00:00+09:00",
            "eventCount": 6,
            "uniqueDeviceCount": 6,
            "averageRssi": -61.2
          }
        ],
        "windowMinutes": 36
      },
      "delta": {
        "totalEvents": 3,
        "uniqueDevices": 2,
        "averageRssi": 3.8
      }
    }
  },
  "healthKpis": {
    "totalAnchors": 12,
    "onlineAnchors": 9,
    "degradedAnchors": 2,
    "offlineAnchors": 1,
    "unknownAnchors": 0,
    "healthyRatePercent": 75.0,
    "reachableRatePercent": 91.7
  },
  "events": [
    {
      "id": 999,
      "deviceMac": "aa:aa:aa:aa:aa:01",
      "deviceName": "Visitor Device",
      "rssiDbm": -50,
      "scannedAt": "2026-04-28T14:40:12+09:00",
      "receivedAt": "2026-04-28T14:40:14+09:00",
      "anchor": {
        "id": 101,
        "anchorUid": "aa:bb:cc:dd:ee:01",
        "label": "Anchor A"
      }
    }
  ]
}
```

## 핵심 필드 설명

- `stats.totalEvents`: 현재 구간 이벤트 수
- `stats.uniqueDevices`: 현재 구간 고유 디바이스 수
- `stats.averageRssi`: 현재 구간 평균 RSSI
- `comparison.previousWeek.stats.*`: 지난주 동일 시간 구간 값
- `comparison.previousWeek.delta.*`: 현재값 - 전주값
- `healthKpis.healthyRatePercent`: `online / total * 100`
- `healthKpis.reachableRatePercent`: `(online + degraded) / total * 100`

## delta 해석 규칙

- `delta.totalEvents > 0`
  - 지난주 같은 시간보다 이벤트가 증가
- `delta.totalEvents < 0`
  - 지난주 같은 시간보다 이벤트가 감소
- `delta.uniqueDevices > 0`
  - 지난주 같은 시간보다 감지된 고유 디바이스가 증가
- `delta.averageRssi > 0`
  - 평균 RSSI가 덜 음수 방향으로 이동
  - 예: `-61.2 -> -57.4` 이면 `+3.8`

## 프론트 UI 요구사항

### 1. 비교 KPI 카드

상단 카드 예시:

- Current Total Events
- Current Unique Devices
- Health Check Rate

표시 규칙:

- delta 양수면 상승 색상
- delta 음수면 하락 색상
- delta 0이면 중립 색상
- `healthyRatePercent`는 업타임처럼 큰 퍼센트 숫자로 표시
- 작은 보조 문구로 `onlineAnchors / totalAnchors anchors healthy` 표시

### 2. 비교 문구

예시:

- `지난주 같은 시간보다 이벤트 3건 증가`
- `지난주 같은 시간보다 고유 디바이스 2대 증가`
- `평균 RSSI 3.8 dBm 개선`

### 3. 시간 범위 표시

현재 구간과 비교 구간을 둘 다 명확히 보여준다.

예시:

- `현재: 2026-04-28 14:07 ~ 14:43`
- `비교: 2026-04-21 14:07 ~ 14:43`

### 4. 차트

권장 차트:

- 현재 vs 전주 `timeSeries` 비교 라인/바 차트
- anchor별 eventCount 비교 막대 차트

### 5. 빈 상태

- 현재 구간 데이터 없음:
  - `이 시간대에 수집된 스캔 데이터가 없습니다`
- 전주 비교 구간 데이터 없음:
  - 현재 데이터는 보여주고, 비교는 `No previous-week data`

## 프론트 구현용 타입 예시

```ts
interface ScanDashboardAnchorStat {
  anchorId: number | null;
  anchorUid: string | null;
  label: string | null;
  eventCount: number;
  averageRssi: number | null;
  lastScannedAt: string | null;
}

interface ScanDashboardTimeSeriesItem {
  bucket: string;
  eventCount: number;
  uniqueDeviceCount: number;
  averageRssi: number | null;
}

interface ScanDashboardStats {
  totalEvents: number;
  uniqueDevices: number;
  averageRssi: number | null;
  latestEventScannedAt?: string | null;
  anchorBreakdown: ScanDashboardAnchorStat[];
  timeSeries: ScanDashboardTimeSeriesItem[];
  windowMinutes: number;
}

interface ScanDashboardComparison {
  previousWeek: {
    timespan: {
      since: string;
      until: string;
    };
    stats: ScanDashboardStats;
    delta: {
      totalEvents: number;
      uniqueDevices: number;
      averageRssi: number | null;
    };
  };
}

interface DashboardHealthKpis {
  totalAnchors: number;
  onlineAnchors: number;
  degradedAnchors: number;
  offlineAnchors: number;
  unknownAnchors: number;
  healthyRatePercent: number | null;
  reachableRatePercent: number | null;
}

interface ScanDashboardResponse {
  timespan: {
    since: string;
    until: string;
    limit: number;
  };
  stats: ScanDashboardStats;
  comparison: ScanDashboardComparison;
  healthKpis: DashboardHealthKpis;
}
```

## 프론트 프로젝트에 전달할 프롬프트 예시

```text
BLE scan dashboard 비교 화면을 구현해줘.

백엔드 API:
GET /api/spaces/:spaceId/rooms/:roomId/ble_scan_events/dashboard?since=:since&until=:until&limit=:limit

이 API는 현재 조회 구간과 함께 comparison.previousWeek에 지난주 동일 시간대 비교 데이터를 내려준다.
예를 들어 현재가 2026-04-28 14:07~14:43이면 비교 구간은 2026-04-21 14:07~14:43이다.

화면 요구사항:
- 상단 KPI 카드 3개를 보여줘: totalEvents, uniqueDevices, healthyRatePercent
- totalEvents와 uniqueDevices는 지난주 동일 시간 대비 % 증감을 함께 보여줘
- healthyRatePercent는 업타임 카드처럼 큰 퍼센트 숫자로 보여줘
- healthyRatePercent 아래에 onlineAnchors / totalAnchors 보조 문구를 보여줘
- 필요하면 reachableRatePercent를 작은 서브 텍스트나 tooltip으로 보여줘
- 현재 구간과 전주 동일 구간의 시간을 함께 표시
- 현재 vs 전주 timeSeries 비교 차트를 보여줘
- anchorBreakdown 비교도 볼 수 있게 해줘
- delta가 양수/음수/0인지에 따라 시각적으로 구분해줘
- 전주 데이터가 없으면 graceful fallback을 보여줘
- 모바일과 데스크톱 모두 읽기 좋은 반응형 UI로 만들어줘
```
