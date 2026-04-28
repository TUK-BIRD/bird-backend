# BLE Anchor Health Dashboard Prompt Spec

## 목적

프론트 대시보드에서 room 단위 BLE anchor 상태를 한눈에 보여준다.

핵심 표시 대상:

- `healthState`
- 마지막 health 수신 시각
- `scanEnabled`
- `wifiConnected`
- `mqttConnected`

## 백엔드 엔드포인트

```text
GET /api/spaces/{spaceId}/rooms/{roomId}/ble_anchors/health
```

- 인증 필요: `auth:sanctum`
- 권한 규칙:
  - 사용자가 해당 `space` 멤버면 조회 가능
  - `room` 이 `space` 소속이 아니면 `404`

## 응답 예시

```json
{
  "roomId": 12,
  "spaceId": 3,
  "summary": {
    "total": 4,
    "online": 2,
    "degraded": 1,
    "offline": 1,
    "unknown": 0
  },
  "anchors": [
    {
      "id": 101,
      "anchorUid": "aa:bb:cc:dd:ee:ff",
      "label": "Entrance Anchor",
      "roomId": 12,
      "installedAt": "2026-04-28T13:00:00+09:00",
      "healthState": "online",
      "healthStatus": "online",
      "healthIsStale": false,
      "lastHealthPayloadAt": "2026-04-28T14:01:00+09:00",
      "wifiConnected": true,
      "mqttConnected": true,
      "scanEnabled": true
    },
    {
      "id": 102,
      "anchorUid": "aa:bb:cc:dd:ee:11",
      "label": "Hallway Anchor",
      "roomId": 12,
      "installedAt": "2026-04-28T13:10:00+09:00",
      "healthState": "degraded",
      "healthStatus": "online",
      "healthIsStale": false,
      "lastHealthPayloadAt": "2026-04-28T14:00:30+09:00",
      "wifiConnected": true,
      "mqttConnected": true,
      "scanEnabled": false
    }
  ]
}
```

## 필드 설명

### top-level

- `roomId`: 현재 room ID
- `spaceId`: 현재 space ID
- `summary.total`: 설치된 anchor 개수
- `summary.online`: 정상 장비 개수
- `summary.degraded`: 주의 상태 장비 개수
- `summary.offline`: 오프라인 장비 개수
- `summary.unknown`: 아직 health 미수신 장비 개수
- `anchors`: 장비 목록

### anchor item

- `id`: anchor PK
- `anchorUid`: scanner MAC 주소
- `label`: 사용자 표시 이름
- `roomId`: room ID
- `installedAt`: 설치 시각 ISO8601
- `healthState`: 대시보드 표시용 최종 상태
- `healthStatus`: MQTT payload 원본 status 값
- `healthIsStale`: 마지막 health 가 timeout 기준으로 오래되었는지 여부
- `lastHealthPayloadAt`: 마지막 health 수신 시각 ISO8601
- `wifiConnected`: Wi-Fi 연결 여부
- `mqttConnected`: MQTT 연결 여부
- `scanEnabled`: BLE scan 활성 여부

## 상태 해석 규칙

- `healthState == "online"`
  - 장비가 정상 heartbeat 상태
- `healthState == "degraded"`
  - health 는 online 이지만 일부 운영 이상
  - 예: `scanEnabled == false`, `wifiConnected == false`
- `healthState == "offline"`
  - payload status 가 offline 이거나 health timeout 초과
- `healthState == "unknown"`
  - 아직 health 정보가 없음

## 프론트 UI 요구사항

### 1. 상단 요약 카드

표시 항목:

- Total Anchors
- Online
- Degraded
- Offline
- Unknown

권장 색상:

- `online`: green
- `degraded`: amber/orange
- `offline`: red
- `unknown`: gray

### 2. anchor 상태 리스트 또는 테이블

각 row/card 에 최소 표시:

- anchor label
- anchor UID
- `healthState` badge
- 마지막 수신 시각
- Wi-Fi 상태
- MQTT 상태
- Scan 상태

권장 정렬:

- `offline` 우선
- 다음 `degraded`
- 다음 `online`
- 같은 상태면 최근 설치순 또는 label 순

### 3. 상태 배지 규칙

- `healthState`
  - online: 초록 배지
  - degraded: 주황 배지
  - offline: 빨강 배지
  - unknown: 회색 배지

- `wifiConnected`
  - true: `Wi-Fi Connected`
  - false: `Wi-Fi Disconnected`
  - null: `Wi-Fi Unknown`

- `mqttConnected`
  - true: `MQTT Connected`
  - false: `MQTT Disconnected`
  - null: `MQTT Unknown`

- `scanEnabled`
  - true: `Scan On`
  - false: `Scan Off`
  - null: `Scan Unknown`

### 4. 마지막 수신 시각 표시 규칙

- 기본: 상대 시간과 절대 시간 둘 다 제공
- 예시:
  - `10초 전`
  - tooltip 또는 secondary text: `2026-04-28 14:01:00`

- `lastHealthPayloadAt == null`
  - `No health received`

### 5. 빈 상태 / 오류 상태

- anchor 가 0개면:
  - `No anchors installed in this room`
- API 오류면:
  - 재시도 버튼 제공
  - room/space 권한 오류 메시지 분리 가능하면 분리

## 프론트 구현용 타입 예시

```ts
type HealthState = "online" | "degraded" | "offline" | "unknown";

interface AnchorHealthSummary {
  total: number;
  online: number;
  degraded: number;
  offline: number;
  unknown: number;
}

interface AnchorHealthItem {
  id: number;
  anchorUid: string;
  label: string;
  roomId: number;
  installedAt: string | null;
  healthState: HealthState;
  healthStatus: string | null;
  healthIsStale: boolean;
  lastHealthPayloadAt: string | null;
  wifiConnected: boolean | null;
  mqttConnected: boolean | null;
  scanEnabled: boolean | null;
}

interface RoomAnchorHealthResponse {
  roomId: number;
  spaceId: number;
  summary: AnchorHealthSummary;
  anchors: AnchorHealthItem[];
}
```

## 프론트 프로젝트에 전달할 프롬프트 예시

아래 문장을 프론트 프로젝트 작업 프롬프트로 그대로 사용할 수 있다.

```text
Room BLE Anchor Health Dashboard를 구현해줘.

백엔드 API:
GET /api/spaces/:spaceId/rooms/:roomId/ble_anchors/health

응답 타입:
- roomId: number
- spaceId: number
- summary: { total, online, degraded, offline, unknown }
- anchors: [
    {
      id,
      anchorUid,
      label,
      roomId,
      installedAt,
      healthState: "online" | "degraded" | "offline" | "unknown",
      healthStatus,
      healthIsStale,
      lastHealthPayloadAt,
      wifiConnected,
      mqttConnected,
      scanEnabled
    }
  ]

요구사항:
- 상단에 summary 카드 5개를 보여줘: Total, Online, Degraded, Offline, Unknown
- 아래에는 anchor 리스트 또는 테이블을 보여줘
- 각 anchor 행에 label, anchorUid, healthState, lastHealthPayloadAt, wifiConnected, mqttConnected, scanEnabled를 표시해줘
- healthState는 색상 배지로 구분해줘
- lastHealthPayloadAt는 상대시간과 절대시간을 함께 보여줘
- offline/degraded 장비가 위로 오도록 정렬해줘
- loading, empty, error 상태를 처리해줘
- 모바일과 데스크톱 모두 읽기 쉽게 반응형으로 만들어줘
```
