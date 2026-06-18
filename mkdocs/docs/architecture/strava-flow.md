# Strava API Integration Flow

## Activity Synchronization Flow (UI Perspective)

```mermaid
sequenceDiagram
    participant Browser as Web Browser
    participant Sync as SyncController::sync
    participant Bus as MessageBus
    participant Queue as Message Queue
    participant Handler as SyncActivitiesMessageHandler
    participant Synchronizer as Synchronizer
    participant Fetcher as ActivityFetcher
    participant TokenCache as TokenCache
    participant StravaAPI as Strava API
    participant Repo as ActivityRepository
    participant DB as Database

    Browser->>Sync: POST /sync
    
    Sync->>Sync: authenticate user
    Sync->>Bus: dispatch(SyncActivitiesMessage)
    Bus->>Queue: enqueue message
    Queue-->>Sync: message accepted
    Sync-->>Browser: 202 ACCEPTED<br/>{"status": "syncing"}
    Browser->>Browser: show loading state
    
    par Async Background Job
        Queue->>Handler: process SyncActivitiesMessage
        
        Handler->>Synchronizer: sync(userId)
        
        Synchronizer->>Repo: findLastOrNull(userId)
        Repo->>DB: query last activity
        DB-->>Repo: Activity | null
        Repo-->>Synchronizer: last activity
        
        loop For each page of activities
            Synchronizer->>Fetcher: fetch(userId, page)
            
            Fetcher->>TokenCache: getToken(userId)
            TokenCache-->>Fetcher: OAuth Token
            
            Fetcher->>StravaAPI: GET /athlete/activities
            StravaAPI-->>Fetcher: Activity[]
            
            Fetcher-->>Synchronizer: Activity[]
            
            loop For each activity
                Synchronizer->>Repo: findOrNull(userId, activityId)
                Repo->>DB: query activity
                DB-->>Repo: Activity | null
                Repo-->>Synchronizer: existing?
                
                alt Activity doesn't exist
                    Synchronizer->>Repo: persist(userId, activity)
                    Repo->>DB: insert activity
                    DB-->>Repo: ok
                else Activity exists
                    Note over Synchronizer: skip (already synced)
                end
            end
        end
        
        Synchronizer-->>Handler: sync complete
    end
    
    par User fetches activities
        Browser->>Browser: wait for sync
        Browser->>Browser: poll or receive notification
        Browser->>ActivitiesController: GET /activities
        
        ActivitiesController->>Repo: findRecentByStravaUser(userId, limit)
        Repo->>DB: query recent activities
        DB-->>Repo: Activity[]
        Repo-->>ActivitiesController: activities
        
        ActivitiesController->>ActivitiesController: render template
        ActivitiesController-->>Browser: 200 OK + HTML
        Browser->>Browser: display activities
    end
```

## Activity Stream Download Flow

```mermaid
sequenceDiagram
    participant Service as ApplicationService
    participant Downloader as ActivityStreamDownloader
    participant TokenCache as TokenCache
    participant StravaAPI as Strava API
    participant Factory as ActivityStreamFactory
    participant Cache as ActivityStreamCache
    participant DB as Database

    Service->>Downloader: download(userId, activityId)
    
    Downloader->>TokenCache: getToken(userId)
    TokenCache-->>Downloader: OAuth Token
    
    Downloader->>StravaAPI: GET /activities/{id}/streams<br/>(time, distance, heartrate, velocity)
    StravaAPI-->>Downloader: Stream data (JSON)
    
    Downloader->>Factory: fromJsonString(json)
    Factory-->>Downloader: ActivityStream
    
    Downloader-->>Service: ActivityStream
    
    Service->>Cache: store(userId, activityId, stream)
    Cache->>DB: persist stream data
    DB-->>Cache: ok
```

## Complete Request-Response Cycle (Web)

```mermaid
sequenceDiagram
    participant Browser as Web Browser
    participant Controller as ActivityController
    participant Service as ActivityService
    participant Core as Core Layer<br/>(Analyzer)
    participant Supporting as Supporting Layer<br/>(Repositories)
    participant DB as Database

    Browser->>Controller: GET /api/activities/{id}/analysis
    
    Controller->>Service: analyzeActivity(activityId)
    
    Service->>Supporting: findActivityWithStreams(activityId)
    Supporting->>DB: query activity + streams
    DB-->>Supporting: data
    Supporting-->>Service: ActivityWithStreams
    
    Service->>Core: analyze(activity)
    Core->>Core: calculate metrics<br/>(pace zones, heart rate)
    Core-->>Service: Analysis
    
    Service-->>Controller: AnalysisDTO
    
    Controller->>Controller: serialize to JSON
    Controller-->>Browser: 200 OK + JSON Response
    Browser->>Browser: render analysis
```

## Layer Mapping

| Layer | Component | Responsibility |
|-------|-----------|-----------------|
| **Integration** | FetchActivitiesCommand | CLI entry point, orchestrates sync |
| **Supporting** | Synchronizer | Coordinates fetching and persistence |
| **Supporting** | ActivityFetcher | HTTP calls to Strava API |
| **Supporting** | ActivityStreamDownloader | Downloads stream data from Strava |
| **Supporting** | ActivityRepository | Data access layer |
| **Supporting** | TokenCache | Token management |
| **Core** | Activity, ActivityStream | Domain models |
| **Generic** | HttpClientInterface, Symfony | Framework abstraction |
